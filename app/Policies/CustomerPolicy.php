<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\Roles;

class CustomerPolicy extends BasePolicy
{
    /**
     * ADMIN+ can manage customers; MANAGER and ACCOUNTANT can view them
     * read-only; EMPLOYEE has no access. CLIENT never browses the full
     * list, only their own linked customer (see view() below).
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
        if ($this->viewAny($user)) {
            return true;
        }

        return $user->hasRole(Roles::CLIENT) && $user->customer_id === $customer->id;
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
