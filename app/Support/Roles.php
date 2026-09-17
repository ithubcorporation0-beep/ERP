<?php

namespace App\Support;

class Roles
{
    public const SUPER_ADMIN = 'SUPER_ADMIN';

    public const ADMIN = 'ADMIN';

    public const MANAGER = 'MANAGER';

    public const ACCOUNTANT = 'ACCOUNTANT';

    public const EMPLOYEE = 'EMPLOYEE';

    public const CLIENT = 'CLIENT';

    /**
     * All roles, highest privilege first. Also determines which role's
     * dashboard a user with multiple roles is redirected to after login.
     */
    public const ALL = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::MANAGER,
        self::ACCOUNTANT,
        self::EMPLOYEE,
        self::CLIENT,
    ];

    /**
     * Maps each role to the route name of its dashboard.
     */
    public const DASHBOARD_ROUTES = [
        self::SUPER_ADMIN => 'roles.super',
        self::ADMIN => 'roles.admin',
        self::MANAGER => 'roles.manager',
        self::ACCOUNTANT => 'roles.accountant',
        self::EMPLOYEE => 'roles.employee',
        self::CLIENT => 'roles.client',
    ];

    /**
     * The route name for the dashboard of the user's highest-privilege role,
     * falling back to the default dashboard if the user has no role.
     */
    public static function dashboardRouteFor(\App\Models\User $user): string
    {
        $role = self::highestRoleFor($user);

        return $role ? self::DASHBOARD_ROUTES[$role] : 'dashboard';
    }

    /**
     * The user's highest-privilege role name, or null if they are a guest
     * or have no role assigned.
     */
    public static function highestRoleFor(?\App\Models\User $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (self::ALL as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }
}
