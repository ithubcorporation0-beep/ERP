<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Setting;
use App\Support\SettingKeys;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $expenses = $this->filteredQuery($request)
            ->with(['customer', 'project'])
            ->orderByDesc('expense_date')
            ->paginate(20)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::cases(),
            'projects' => Project::orderBy('name')->get(),
            'category' => $request->string('category')->toString(),
            'projectId' => $request->integer('project_id') ?: null,
            'dateFrom' => $request->string('date_from')->toString(),
            'dateTo' => $request->string('date_to')->toString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('expenses.create', [
            'expense' => new Expense(),
            'categories' => ExpenseCategory::cases(),
            'customers' => Customer::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'defaultCurrency' => Setting::get(SettingKeys::DEFAULT_CURRENCY, 'USD'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::create($request->validated());

        return redirect()->route('expenses.index')
            ->with('status', 'Expense created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense): View
    {
        return view('expenses.show', [
            'expense' => $expense->load(['customer', 'project']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense): View
    {
        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::cases(),
            'customers' => Customer::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'defaultCurrency' => Setting::get(SettingKeys::DEFAULT_CURRENCY, 'USD'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')
            ->with('status', 'Expense updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('status', 'Expense deleted.');
    }

    /**
     * Basic report: totals by month and by category, respecting the
     * same filters as the index listing.
     */
    public function report(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = $this->filteredQuery($request)->get();

        $byMonth = $expenses
            ->groupBy(fn (Expense $expense) => $expense->expense_date->format('Y-m'))
            ->map(fn ($group) => $group->sum(fn (Expense $expense) => (float) $expense->amount))
            ->sortKeys();

        $byCategory = collect(ExpenseCategory::cases())
            ->mapWithKeys(fn (ExpenseCategory $category) => [
                $category->value => $expenses
                    ->where('category', $category)
                    ->sum(fn (Expense $expense) => (float) $expense->amount),
            ]);

        return view('expenses.report', [
            'byMonth' => $byMonth,
            'byCategory' => $byCategory,
            'total' => $expenses->sum(fn (Expense $expense) => (float) $expense->amount),
            'categories' => ExpenseCategory::cases(),
            'projects' => Project::orderBy('name')->get(),
            'category' => $request->string('category')->toString(),
            'projectId' => $request->integer('project_id') ?: null,
            'dateFrom' => $request->string('date_from')->toString(),
            'dateTo' => $request->string('date_to')->toString(),
        ]);
    }

    /**
     * CSV export of the filtered expense list.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = $this->filteredQuery($request)->with(['customer', 'project'])->orderBy('expense_date')->get();

        return Response::streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Category', 'Customer', 'Project', 'Amount', 'Currency', 'Notes']);

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->expense_date->format('Y-m-d'),
                    $expense->category->label(),
                    $expense->customer?->name,
                    $expense->project?->name,
                    $expense->amount,
                    $expense->currency,
                    $expense->notes,
                ]);
            }

            fclose($handle);
        }, 'expenses-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Apply the category/project/date-range filters shared by the index,
     * report, and export actions.
     */
    private function filteredQuery(Request $request): Builder
    {
        return Expense::query()
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->date('date_to')));
    }
}
