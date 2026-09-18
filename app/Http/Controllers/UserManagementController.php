<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Admin user & role management. Every action is ADMIN+ only, enforced by
 * UserPolicy (viewAny/create/update/delete) plus the business rules below
 * that don't fit a policy because they depend on the target user's own
 * state: SUPER_ADMIN can't be demoted (or deleted), and a user with
 * active task/project links can't be deleted outright.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($query) => $query->whereHas(
                'roles',
                fn ($q) => $q->where('name', $request->string('role')->toString())
            ))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Roles::ALL,
            'statuses' => UserStatus::cases(),
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->string('status')->toString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'roles' => Roles::ALL,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->validateRolesAndCustomer($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'customer_id' => in_array(Roles::CLIENT, $data['roles'], true) ? $data['customer_id'] : null,
        ]);

        $user->syncRoles($data['roles']);

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'targetUser' => $user,
            'roles' => Roles::ALL,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    /**
     * Replace the user's role set and, for CLIENT, their linked customer.
     * Roles live in spatie/permission's pivot table, not a column, so
     * they aren't caught by Auditable's automatic attribute diffing —
     * logged explicitly here instead. The customer_id change (a real
     * column) is still auto-logged by Auditable when it changes.
     */
    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $this->validateRolesAndCustomer($request);

        $oldRoles = $user->roles->pluck('name')->all();

        if (in_array(Roles::SUPER_ADMIN, $oldRoles, true) && ! in_array(Roles::SUPER_ADMIN, $data['roles'], true)) {
            return back()->withErrors(['roles' => 'The SUPER_ADMIN role cannot be removed from this user.'])->withInput();
        }

        $user->update([
            'customer_id' => in_array(Roles::CLIENT, $data['roles'], true) ? $data['customer_id'] : null,
        ]);

        $oldRolesSorted = collect($oldRoles)->sort()->values()->all();
        $newRolesSorted = collect($data['roles'])->sort()->values()->all();

        if ($oldRolesSorted !== $newRolesSorted) {
            $user->syncRoles($data['roles']);

            activity()
                ->performedOn($user)
                ->causedBy($request->user())
                ->withProperties(['old_roles' => $oldRoles, 'new_roles' => $data['roles']])
                ->event('updated')
                ->log('Roles changed');
        }

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['status' => UserStatus::SUSPENDED]);

        return back()->with('status', 'User suspended.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['status' => UserStatus::ACTIVE]);

        return back()->with('status', 'User activated.');
    }

    /**
     * A SUPER_ADMIN is never deletable (the ultimate form of demotion),
     * and a user still assigned to tasks or project-memberships can't be
     * silently removed: both those pivot tables cascade-delete on the
     * user, so deleting them here would quietly wipe that history.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return back()->with('warning', 'A SUPER_ADMIN user cannot be deleted.');
        }

        $taskCount = $user->assignedTasks()->count();
        $projectCount = $user->projects()->count();

        if ($taskCount > 0 || $projectCount > 0) {
            return back()->with(
                'warning',
                "This user can't be deleted: they are assigned to {$taskCount} task(s) and a member of {$projectCount} project(s). Remove those links first."
            );
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User deleted.');
    }

    /**
     * Shared validation for store()/updateRoles(): a non-empty set of
     * known roles, plus a customer required if and only if CLIENT is
     * among them (a user can only ever be linked to one Customer, so
     * that single-FK column already enforces "exactly one").
     *
     * @param  array<string, mixed>  $extra  Additional rules to merge in (e.g. name/email/password for store()).
     * @return array<string, mixed>
     */
    private function validateRolesAndCustomer(Request $request, array $extra = []): array
    {
        return $request->validate(array_merge($extra, [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(Roles::ALL)],
            'customer_id' => [
                Rule::requiredIf(fn () => in_array(Roles::CLIENT, $request->input('roles', []), true)),
                'nullable',
                'exists:customers,id',
            ],
        ]));
    }
}
