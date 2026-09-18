<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;

class PaymentPolicy extends BasePolicy
{
    /**
     * ADMIN+ and ACCOUNTANT have full access; MANAGER is read-only;
     * CLIENT is read-only and limited to their own customer's payments
     * (enforced in view()/scopeForUser()). EMPLOYEE is denied.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoles($user, [
            Roles::SUPER_ADMIN,
            Roles::ADMIN,
            Roles::ACCOUNTANT,
            Roles::MANAGER,
            Roles::CLIENT,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::ACCOUNTANT, Roles::MANAGER])) {
            return true;
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $user->customer_id !== null && $user->customer_id === $payment->customer_id;
        }

        return false;
    }

    /**
     * Only ADMIN+ and ACCOUNTANT record payments; MANAGER is read-only.
     */
    public function create(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::ACCOUNTANT]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $this->create($user);
    }

    /**
     * Scope a payments query to what the given user is allowed to see,
     * matching the view() rules above.
     */
    public function scopeForUser(Builder $query, User $user, string $ownerColumn = 'user_id'): Builder
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::ACCOUNTANT, Roles::MANAGER])) {
            return $query;
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $query->where('customer_id', $user->customer_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
