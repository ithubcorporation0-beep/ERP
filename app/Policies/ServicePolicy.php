<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Support\Roles;

class ServicePolicy extends BasePolicy
{
    /**
     * ADMIN+, MANAGER, and ACCOUNTANT can view the catalog (MANAGER and
     * ACCOUNTANT read-only). Everyone else is denied.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoles($user, [
            Roles::SUPER_ADMIN,
            Roles::ADMIN,
            Roles::MANAGER,
            Roles::ACCOUNTANT,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Service $service): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Service $service): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Service $service): bool
    {
        return $this->create($user);
    }
}
