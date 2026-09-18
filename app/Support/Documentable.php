<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the route segment used by the document routes (e.g. "customers")
 * to the Eloquent model class its documents attach to, so DocumentController
 * can resolve the owning entity from a single generic {type}/{id} pair
 * instead of six near-identical controllers.
 */
class Documentable
{
    /**
     * @return array<string, class-string<Model>>
     */
    public static function map(): array
    {
        return [
            'customers' => Customer::class,
            'projects' => Project::class,
            'tasks' => Task::class,
            'invoices' => Invoice::class,
            'payments' => Payment::class,
            'expenses' => Expense::class,
        ];
    }

    public static function resolve(string $type, int|string $id): Model
    {
        $class = self::map()[$type] ?? abort(404);

        return $class::findOrFail($id);
    }
}
