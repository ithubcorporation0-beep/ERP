<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\Roles;

class CustomerPolicy extends BasePolicy
{
    /**
     * ADMIN+ can manage customers; MANAGER and ACCOUNTANT can view them
     * read-only; EMPLOYEE has no access. CLIENT access is limited to
     * their own linked customer record, which isn't wired up yet, so
     * CLIENT is denied here until that relationship exists.
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
    public function view(User $user, Customer $customer): bool
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
    public function update(User $user, Customer $customer): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $this->create($user);
    }
}
