<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One method per role dashboard, each returning the "quick stats" that role
 * cares about. Where a policy already exposes a scopeForUser() (Project,
 * Task, Invoice, Payment), stats are computed through it so a Manager,
 * Employee, or Client dashboard never counts records that role couldn't
 * otherwise see via the matching index page.
 */
class DashboardController extends Controller
{
    public function superAdmin(Request $request): View
    {
        return view('roles.super', $this->organizationWideStats($request));
    }

    public function admin(Request $request): View
    {
        return view('roles.admin', $this->organizationWideStats($request));
    }

    public function manager(Request $request): View
    {
        $user = $request->user();

        $tasks = app(TaskPolicy::class)->scopeForUser(Task::query(), $user);
        $projects = app(ProjectPolicy::class)->scopeForUser(Project::query(), $user);

        return view('roles.manager', [
            'activeProjects' => (clone $projects)->where('status', ProjectStatus::IN_PROGRESS)->count(),
            'openTasks' => (clone $tasks)->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])->count(),
            'tasksDueThisWeek' => (clone $tasks)
                ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
                ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->count(),
            'teamMembers' => User::role(Roles::EMPLOYEE)->count(),
        ]);
    }

    public function accountant(Request $request): View
    {
        $user = $request->user();

        $invoices = app(InvoicePolicy::class)->scopeForUser(Invoice::query(), $user);
        $payments = app(PaymentPolicy::class)->scopeForUser(Payment::query(), $user);

        return view('roles.accountant', [
            'openInvoices' => (clone $invoices)->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID])->count(),
            'balanceDue' => (clone $invoices)->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID])->sum('balance_due'),
            'paymentsThisMonth' => (clone $payments)->whereBetween('received_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'expensesThisMonth' => Expense::query()->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
        ]);
    }

    public function employee(Request $request): View
    {
        $user = $request->user();

        $myTasks = $user->assignedTasks();

        return view('roles.employee', [
            'myOpenTasks' => (clone $myTasks)->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])->count(),
            'myTasksDueThisWeek' => (clone $myTasks)
                ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
                ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->count(),
            'myProjects' => $user->projects()->count(),
        ]);
    }

    public function client(Request $request): View
    {
        $customerId = $request->user()->customer_id;

        $invoices = Invoice::query()->where('customer_id', $customerId);

        return view('roles.client', [
            'myOpenInvoices' => (clone $invoices)->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID])->count(),
            'myBalanceDue' => (clone $invoices)->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID])->sum('balance_due'),
            'myActiveProjects' => Project::query()->where('customer_id', $customerId)->where('status', ProjectStatus::IN_PROGRESS)->count(),
        ]);
    }

    /**
     * Shared by Super Admin and Admin: both see the whole organization, so
     * there's no scoping to apply.
     */
    private function organizationWideStats(Request $request): array
    {
        $invoices = Invoice::query()->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID]);

        return [
            'totalUsers' => User::count(),
            'totalCustomers' => Customer::count(),
            'activeProjects' => Project::query()->where('status', ProjectStatus::IN_PROGRESS)->count(),
            'openInvoices' => (clone $invoices)->count(),
            'balanceDue' => (clone $invoices)->sum('balance_due'),
        ];
    }
}
