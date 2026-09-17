<?php

namespace App\Support;

class ErpNavigation
{
    /**
     * The ERP's primary navigation items.
     *
     * Every authenticated user sees the full list for now; role-based
     * visibility will filter this array once RBAC lands.
     */
    public static function items(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Customers', 'route' => 'customers.index'],
            ['label' => 'Projects', 'route' => 'projects.index'],
            ['label' => 'Tasks', 'route' => 'tasks.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'Expenses', 'route' => 'expenses.index'],
            ['label' => 'Documents', 'route' => 'documents.index'],
            ['label' => 'Users', 'route' => 'users.index'],
            ['label' => 'Settings', 'route' => 'settings.index'],
        ];
    }
}
