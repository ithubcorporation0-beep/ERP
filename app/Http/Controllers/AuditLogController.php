<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Admin-only audit log viewer. Gated entirely by the 'viewAdmin' Gate at
 * the route level (ADMIN/SUPER_ADMIN only) — see routes/web.php.
 */
class AuditLogController extends Controller
{
    /**
     * Friendly label => model class, for the entity filter dropdown and
     * for rendering each row's subject type.
     *
     * @var array<string, class-string>
     */
    private const ENTITY_TYPES = [
        'User' => User::class,
        'Customer' => Customer::class,
        'Project' => Project::class,
        'Task' => Task::class,
        'Invoice' => Invoice::class,
        'Payment' => Payment::class,
        'Expense' => Expense::class,
        'Document' => Media::class,
    ];

    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->filled('subject_type'), fn ($query) => $query->where('subject_type', $request->string('subject_type')->toString()))
            ->when($request->filled('causer_id'), fn ($query) => $query->where('causer_id', $request->integer('causer_id'))->where('causer_type', User::class))
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('audit-log.index', [
            'activities' => $activities,
            'entityTypes' => self::ENTITY_TYPES,
            'actors' => User::orderBy('name')->get(),
            'events' => ['created', 'updated', 'deleted'],
            'subjectType' => $request->string('subject_type')->toString(),
            'causerId' => $request->integer('causer_id') ?: null,
            'event' => $request->string('event')->toString(),
            'dateFrom' => $request->string('date_from')->toString(),
            'dateTo' => $request->string('date_to')->toString(),
        ]);
    }
}
