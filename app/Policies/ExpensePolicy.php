<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Support\Roles;

class ExpensePolicy extends BasePolicy
{
    /**
     * ADMIN+ and ACCOUNTANT have full access; MANAGER is read-only.
     * Everyone else (EMPLOYEE, CLIENT) has no access at all.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoles($user, [
            Roles::SUPER_ADMIN,
            Roles::ADMIN,
            Roles::ACCOUNTANT,
            Roles::MANAGER,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Expense $expense): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::ACCOUNTANT]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Expense $expense): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $this->create($user);
    }
}
