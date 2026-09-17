<?php

namespace App\Policies;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;

class ProjectPolicy extends BasePolicy
{
    /**
     * ADMIN+ and MANAGER see every project. EMPLOYEE sees only projects
     * they're a member of. CLIENT sees only projects belonging to their
     * own linked customer. Everyone else (e.g. ACCOUNTANT) is denied.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoles($user, [
            Roles::SUPER_ADMIN,
            Roles::ADMIN,
            Roles::MANAGER,
            Roles::EMPLOYEE,
            Roles::CLIENT,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER])) {
            return true;
        }

        if ($user->hasRole(Roles::EMPLOYEE)) {
            return $project->users()->whereKey($user->id)->exists();
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $user->customer_id !== null && $user->customer_id === $project->customer_id;
        }

        return false;
    }

    /**
     * Only ADMIN+ and MANAGER create projects; a project has no OWNER
     * to defer to until it exists.
     */
    public function create(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER]);
    }

    /**
     * ADMIN+ and MANAGER can update any project; otherwise only the
     * project's OWNER member can.
     */
    public function update(User $user, Project $project): bool
    {
        if ($this->create($user)) {
            return true;
        }

        return $project->users()
            ->whereKey($user->id)
            ->wherePivot('role', ProjectMemberRole::OWNER->value)
            ->exists();
    }

    /**
     * Managing members follows the same rule as updating the project.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * ADMIN+ only. A check preventing deletion of projects that still
     * have tasks will be added once the Tasks module exists.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN]);
    }

    /**
     * Scope a projects query to what the given user is allowed to see,
     * matching the view() rules above.
     */
    public function scopeForUser(Builder $query, User $user, string $ownerColumn = 'user_id'): Builder
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER])) {
            return $query;
        }

        if ($user->hasRole(Roles::EMPLOYEE)) {
            return $query->whereHas('users', fn ($q) => $q->whereKey($user->id));
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $query->where('customer_id', $user->customer_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
