<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Minimal admin-only user management: a list with each user's current
 * role, and a single action to change it. Gated entirely by the
 * 'viewAdmin' Gate at the route level (ADMIN/SUPER_ADMIN only).
 */
class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->paginate(20);

        return view('users.index', [
            'users' => $users,
            'roles' => Roles::ALL,
        ]);
    }

    /**
     * Replace the user's role with the selected one. Roles live in
     * spatie/permission's pivot table, not a column, so this is not
     * caught by Auditable's automatic attribute diffing — logged
     * explicitly here instead.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(Roles::ALL)],
        ]);

        $oldRole = Roles::highestRoleFor($user);
        $newRole = $data['role'];

        if ($oldRole !== $newRole) {
            $user->syncRoles([$newRole]);

            activity()
                ->performedOn($user)
                ->causedBy($request->user())
                ->withProperties(['old_role' => $oldRole, 'new_role' => $newRole])
                ->event('updated')
                ->log("Role changed from {$oldRole} to {$newRole}");
        }

        return back()->with('status', 'Role updated.');
    }
}
