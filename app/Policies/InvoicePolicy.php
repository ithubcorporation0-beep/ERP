<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;

class InvoicePolicy extends BasePolicy
{
    /**
     * ADMIN+ and ACCOUNTANT have full access; MANAGER is read-only by
     * default; CLIENT is read-only and limited to their own customer's
     * invoices (enforced in view()/scopeForUser()). EMPLOYEE is denied.
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
    public function view(User $user, Invoice $invoice): bool
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::ACCOUNTANT, Roles::MANAGER])) {
            return true;
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $user->customer_id !== null && $user->customer_id === $invoice->customer_id;
        }

        return false;
    }

    /**
     * Only ADMIN+ and ACCOUNTANT create invoices; MANAGER is read-only.
     */
    public function create(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::ACCOUNTANT]);
    }

    /**
     * ADMIN+ and ACCOUNTANT can update an invoice, but never once it's
     * PAID or VOID — those are financially final states.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if (in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::VOID], true)) {
            return false;
        }

        return $this->create($user);
    }

    /**
     * Managing line items follows the same rule as updating the invoice.
     */
    public function manageItems(User $user, Invoice $invoice): bool
    {
        return $this->update($user, $invoice);
    }

    /**
     * Only a DRAFT invoice can be marked SENT.
     */
    public function markSent(User $user, Invoice $invoice): bool
    {
        return $this->create($user) && $invoice->status === InvoiceStatus::DRAFT;
    }

    /**
     * Any invoice can be voided except one that's already PAID or VOID.
     */
    public function markVoid(User $user, Invoice $invoice): bool
    {
        return $this->create($user)
            && ! in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::VOID], true);
    }

    /**
     * ADMIN+ only, and only while the invoice is still a DRAFT — once
     * numbered and sent, an invoice is a financial record and shouldn't
     * be deleted.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN])
            && $invoice->status === InvoiceStatus::DRAFT;
    }

    /**
     * Scope an invoices query to what the given user is allowed to see,
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
