<?php

namespace App\Support;

use App\Models\Customer;

class ErpNavigation
{
    /**
     * The ERP's primary navigation items.
     *
     * Items with a 'gate' key are only shown when the current user passes
     * that Gate (checked in the Blade view with @can). A 'gate_arg' is
     * passed as the Gate/policy's second argument (e.g. a model class for
     * a "viewAny" policy check). Everything else is visible to any
     * authenticated user for now.
     */
    public static function items(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Customers', 'route' => 'customers.index', 'gate' => 'viewAny', 'gate_arg' => Customer::class],
            ['label' => 'Projects', 'route' => 'projects.index'],
            ['label' => 'Tasks', 'route' => 'tasks.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'Expenses', 'route' => 'expenses.index'],
            ['label' => 'Documents', 'route' => 'documents.index'],
            ['label' => 'Users', 'route' => 'users.index', 'gate' => 'viewAdmin'],
            ['label' => 'Settings', 'route' => 'settings.index', 'gate' => 'viewAdmin'],
        ];
    }
}
