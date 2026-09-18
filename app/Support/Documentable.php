<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * The project and customer a document-carrying entity is associated
     * with, used to fan out DocumentUploadedNotification to that project's
     * members and that customer's CLIENT users. Either may be null.
     *
     * @return array{project: ?Project, customer: ?Customer}
     */
    public static function projectAndCustomerFor(Model $model): array
    {
        return match (true) {
            $model instanceof Customer => ['project' => null, 'customer' => $model],
            $model instanceof Project => ['project' => $model, 'customer' => $model->customer],
            $model instanceof Task => ['project' => $model->project, 'customer' => $model->project?->customer],
            $model instanceof Invoice => ['project' => $model->project, 'customer' => $model->customer],
            $model instanceof Payment => ['project' => $model->invoice?->project, 'customer' => $model->customer],
            $model instanceof Expense => ['project' => $model->project, 'customer' => $model->customer],
            default => ['project' => null, 'customer' => null],
        };
    }

    /**
     * The users who should be notified about a document uploaded to the
     * given entity: the associated project's members, plus the associated
     * customer's CLIENT users. Excludes $exceptUserId (typically the
     * uploader) and returns a de-duplicated collection.
     *
     * @return Collection<int, User>
     */
    public static function documentRecipients(Model $model, ?int $exceptUserId = null): Collection
    {
        ['project' => $project, 'customer' => $customer] = self::projectAndCustomerFor($model);

        $projectMembers = $project?->users ?? collect();

        $clientUsers = $customer
            ? User::role(Roles::CLIENT)->where('customer_id', $customer->id)->get()
            : collect();

        return $projectMembers
            ->merge($clientUsers)
            ->unique('id')
            ->when($exceptUserId, fn (Collection $users) => $users->reject(fn (User $user) => $user->id === $exceptUserId))
            ->values();
    }
}
