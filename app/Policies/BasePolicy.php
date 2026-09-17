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
