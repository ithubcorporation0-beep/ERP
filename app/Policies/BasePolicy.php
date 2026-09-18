<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared authorization helpers for entity policies.
 *
 * Concrete policies (e.g. CustomerPolicy, InvoicePolicy) extend this class
 * to reuse role checks and the query-scoping pattern rather than
 * re-implementing them per entity.
 */
class BasePolicy
{
    /**
     * Whether the given user is a SUPER_ADMIN.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(Roles::SUPER_ADMIN);
    }

    /**
     * Whether the given user has any of the given roles.
     *
     * @param  array<int, string>  $roles
     */
    protected function hasRoles(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Whether the user may upload/replace documents attached to the model.
     * Delegates to the concrete policy's update() so document management
     * follows the same role/ownership rules as editing the entity itself.
     */
    public function uploadDocuments(User $user, mixed $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Whether the user may list/download documents attached to the model.
     * Delegates to view(), which is where each policy already encodes its
     * CLIENT-to-own-customer scoping — no per-entity duplication needed.
     */
    public function downloadDocuments(User $user, mixed $model): bool
    {
        return $this->view($user, $model);
    }

    /**
     * Removing a document follows the same rule as uploading one.
     */
    public function deleteDocuments(User $user, mixed $model): bool
    {
        return $this->update($user, $model);
    }

    /**
     * Scope a query to only the records the given user is allowed to see.
     *
     * SUPER_ADMIN and ADMIN bypass scoping and see every record. Everyone
     * else is restricted to records they own, via $ownerColumn. Override
     * this in a concrete policy when an entity needs different scoping
     * (e.g. a MANAGER seeing their team's records).
     */
    public function scopeForUser(Builder $query, User $user, string $ownerColumn = 'user_id'): Builder
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN])) {
            return $query;
        }

        return $query->where($ownerColumn, $user->id);
    }
}
