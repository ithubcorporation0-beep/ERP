<?php

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
use App\Support\Roles;

test('the super admin dashboard shows organization-wide counts', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Roles::SUPER_ADMIN);

    $customer = Customer::factory()->create();
    Customer::factory()->count(2)->create();
    Project::factory()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::IN_PROGRESS]);
    Project::factory()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::COMPLETED]);
    Invoice::factory()->create(['customer_id' => $customer->id, 'status' => InvoiceStatus::SENT, 'balance_due' => 150]);
    Invoice::factory()->create(['customer_id' => $customer->id, 'status' => InvoiceStatus::DRAFT, 'balance_due' => 999]);

    $response = $this->actingAs($superAdmin)->get('/super');

    $response->assertOk();
    $response->assertViewHas('totalCustomers', 3);
    $response->assertViewHas('activeProjects', 1);
    $response->assertViewHas('openInvoices', 1);
    $response->assertViewHas('balanceDue', fn ($value) => (float) $value === 150.0);
});

test('the manager dashboard scopes task and project counts through TaskPolicy/ProjectPolicy', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);

    Project::factory()->create(['status' => ProjectStatus::IN_PROGRESS]);
    $otherProject = Project::factory()->create(['status' => ProjectStatus::ON_HOLD]);
    Task::factory()->create(['project_id' => $otherProject->id, 'status' => TaskStatus::TODO, 'due_date' => now()->addDays(2)]);
    Task::factory()->create(['project_id' => $otherProject->id, 'status' => TaskStatus::DONE]);

    $response = $this->actingAs($manager)->get('/manager');

    $response->assertOk();
    $response->assertViewHas('activeProjects', 1);
    $response->assertViewHas('openTasks', 1);
    $response->assertViewHas('tasksDueThisWeek', 1);
});

test('the accountant dashboard totals invoices, payments, and expenses', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole(Roles::ACCOUNTANT);

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::SENT, 'balance_due' => 80]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'customer_id' => $invoice->customer_id,
        'amount' => 40,
        'received_date' => now(),
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'customer_id' => $invoice->customer_id,
        'amount' => 999,
        'received_date' => now()->subMonths(2),
    ]);
    Expense::factory()->create(['amount' => 25, 'expense_date' => now()]);
    Expense::factory()->create(['amount' => 999, 'expense_date' => now()->subMonths(2)]);

    $response = $this->actingAs($accountant)->get('/accountant');

    $response->assertOk();
    $response->assertViewHas('openInvoices', 1);
    $response->assertViewHas('balanceDue', '80.00');
    $response->assertViewHas('paymentsThisMonth', '40.00');
    $response->assertViewHas('expensesThisMonth', '25.00');
});

test("the employee dashboard only counts the employee's own assigned tasks and projects", function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $myTask = Task::factory()->create(['status' => TaskStatus::TODO, 'due_date' => now()->addDays(3)]);
    $employee->assignedTasks()->attach($myTask);

    // Someone else's task/project shouldn't be counted.
    Task::factory()->create(['status' => TaskStatus::TODO]);
    $project = Project::factory()->create();
    $employee->projects()->attach($project, ['role' => 'CONTRIBUTOR']);
    Project::factory()->create();

    $response = $this->actingAs($employee)->get('/employee');

    $response->assertOk();
    $response->assertViewHas('myOpenTasks', 1);
    $response->assertViewHas('myTasksDueThisWeek', 1);
    $response->assertViewHas('myProjects', 1);
});

test("the client dashboard only counts the client's own customer invoices and projects", function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    Invoice::factory()->create(['customer_id' => $customer->id, 'status' => InvoiceStatus::SENT, 'balance_due' => 60]);
    Invoice::factory()->create(['customer_id' => $otherCustomer->id, 'status' => InvoiceStatus::SENT, 'balance_due' => 999]);
    Project::factory()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::IN_PROGRESS]);
    Project::factory()->create(['customer_id' => $otherCustomer->id, 'status' => ProjectStatus::IN_PROGRESS]);

    $response = $this->actingAs($client)->get('/client');

    $response->assertOk();
    $response->assertViewHas('myOpenInvoices', 1);
    $response->assertViewHas('myBalanceDue', '60.00');
    $response->assertViewHas('myActiveProjects', 1);
});
