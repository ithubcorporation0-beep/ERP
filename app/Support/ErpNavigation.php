<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\Task;

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
            ['label' => 'Projects', 'route' => 'projects.index', 'gate' => 'viewAny', 'gate_arg' => Project::class],
            ['label' => 'Tasks', 'route' => 'tasks.index', 'gate' => 'viewAny', 'gate_arg' => Task::class],
            ['label' => 'Services', 'route' => 'services.index', 'gate' => 'viewAny', 'gate_arg' => Service::class],
            ['label' => 'Products', 'route' => 'products.index', 'gate' => 'viewAny', 'gate_arg' => Product::class],
            ['label' => 'Invoices', 'route' => 'invoices.index', 'gate' => 'viewAny', 'gate_arg' => Invoice::class],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'Expenses', 'route' => 'expenses.index'],
            ['label' => 'Documents', 'route' => 'documents.index'],
            ['label' => 'Users', 'route' => 'users.index', 'gate' => 'viewAdmin'],
            ['label' => 'Settings', 'route' => 'settings.index', 'gate' => 'viewAdmin'],
        ];
    }
}
