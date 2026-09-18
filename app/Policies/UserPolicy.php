<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;

/**
 * User management (list, create, change roles, suspend/activate, delete)
 * is ADMIN+ only — the same role set as the 'viewAdmin' Gate that hides
 * the Users nav link. Business rules that depend on the target user's
 * own state (can't demote/delete SUPER_ADMIN, can't delete a user with
 * critical linked records) live in UserManagementController, not here,
 * since they aren't about who the actor is.
 */
class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN]);
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }
}
