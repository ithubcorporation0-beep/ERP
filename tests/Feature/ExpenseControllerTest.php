<?php

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Roles::ADMIN);
});

test('an admin can create an expense', function () {
    $response = $this->actingAs($this->admin)->post('/expenses', [
        'category' => ExpenseCategory::TRAVEL->value,
        'amount' => 123.45,
        'currency' => 'USD',
        'expense_date' => now()->format('Y-m-d'),
        'notes' => 'Client visit',
    ]);

    $expense = Expense::first();
    $response->assertRedirect(route('expenses.index'));

    expect($expense)->not->toBeNull()
        ->and($expense->category)->toBe(ExpenseCategory::TRAVEL)
        ->and((float) $expense->amount)->toBe(123.45);
});

test('an admin can update an expense', function () {
    $expense = Expense::factory()->create(['category' => ExpenseCategory::OTHER, 'amount' => 10]);

    $this->actingAs($this->admin)->put(route('expenses.update', $expense), [
        'category' => ExpenseCategory::SOFTWARE->value,
        'amount' => 99.99,
        'currency' => 'USD',
        'expense_date' => now()->format('Y-m-d'),
    ])->assertRedirect(route('expenses.index'));

    expect($expense->fresh()->category)->toBe(ExpenseCategory::SOFTWARE)
        ->and((float) $expense->fresh()->amount)->toBe(99.99);
});

test('an admin can delete an expense', function () {
    $expense = Expense::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    expect(Expense::find($expense->id))->toBeNull();
});

test('a manager can view expenses but cannot create, update, or delete them', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $expense = Expense::factory()->create();

    $this->actingAs($manager)->get('/expenses')->assertOk();

    $this->actingAs($manager)->get(route('expenses.create'))->assertForbidden();
    $this->actingAs($manager)->post('/expenses', [
        'category' => ExpenseCategory::OTHER->value,
        'amount' => 10,
        'currency' => 'USD',
        'expense_date' => now()->format('Y-m-d'),
    ])->assertForbidden();
    $this->actingAs($manager)->put(route('expenses.update', $expense), [
        'category' => ExpenseCategory::OTHER->value,
        'amount' => 10,
        'currency' => 'USD',
        'expense_date' => now()->format('Y-m-d'),
    ])->assertForbidden();
    $this->actingAs($manager)->delete(route('expenses.destroy', $expense))->assertForbidden();
});

test('an employee cannot view the expenses list at all', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($employee)->get('/expenses')->assertForbidden();
});

test('the index can be filtered by category, project, and date range', function () {
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();

    $matching = Expense::factory()->create([
        'category' => ExpenseCategory::TRAVEL,
        'project_id' => $project->id,
        'expense_date' => '2026-03-15',
    ]);
    Expense::factory()->create([
        'category' => ExpenseCategory::SOFTWARE,
        'project_id' => $project->id,
        'expense_date' => '2026-03-15',
    ]);
    Expense::factory()->create([
        'category' => ExpenseCategory::TRAVEL,
        'project_id' => $otherProject->id,
        'expense_date' => '2026-03-15',
    ]);
    Expense::factory()->create([
        'category' => ExpenseCategory::TRAVEL,
        'project_id' => $project->id,
        'expense_date' => '2026-01-01',
    ]);

    $response = $this->actingAs($this->admin)->get('/expenses?'.http_build_query([
        'category' => ExpenseCategory::TRAVEL->value,
        'project_id' => $project->id,
        'date_from' => '2026-03-01',
        'date_to' => '2026-03-31',
    ]));

    $response->assertOk();
    $response->assertViewHas('expenses', function ($expenses) use ($matching) {
        return $expenses->total() === 1 && $expenses->first()->id === $matching->id;
    });
});

test('the report totals expenses correctly by month and by category', function () {
    Expense::factory()->create(['category' => ExpenseCategory::TRAVEL, 'amount' => 100, 'expense_date' => '2026-01-10']);
    Expense::factory()->create(['category' => ExpenseCategory::TRAVEL, 'amount' => 50, 'expense_date' => '2026-01-20']);
    Expense::factory()->create(['category' => ExpenseCategory::SOFTWARE, 'amount' => 25, 'expense_date' => '2026-02-05']);

    $response = $this->actingAs($this->admin)->get(route('expenses.report'));

    $response->assertOk();
    $response->assertViewHas('byMonth', function ($byMonth) {
        return (float) $byMonth['2026-01'] === 150.0 && (float) $byMonth['2026-02'] === 25.0;
    });
    $response->assertViewHas('byCategory', function ($byCategory) {
        return (float) $byCategory[ExpenseCategory::TRAVEL->value] === 150.0
            && (float) $byCategory[ExpenseCategory::SOFTWARE->value] === 25.0
            && (float) $byCategory[ExpenseCategory::SALARY->value] === 0.0;
    });
    $response->assertViewHas('total', function ($total) {
        return (float) $total === 175.0;
    });
});

test('the report respects the same filters as the index', function () {
    $project = Project::factory()->create();
    Expense::factory()->create(['category' => ExpenseCategory::TRAVEL, 'amount' => 100, 'project_id' => $project->id, 'expense_date' => '2026-01-10']);
    Expense::factory()->create(['category' => ExpenseCategory::TRAVEL, 'amount' => 999, 'expense_date' => '2026-01-10']);

    $response = $this->actingAs($this->admin)->get('/expenses/report?'.http_build_query(['project_id' => $project->id]));

    $response->assertOk();
    $response->assertViewHas('total', function ($total) {
        return (float) $total === 100.0;
    });
});

test('a manager can view the report and export but not create expenses', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    Expense::factory()->create();

    $this->actingAs($manager)->get(route('expenses.report'))->assertOk();
    $this->actingAs($manager)->get(route('expenses.export'))->assertOk();
});

test('an employee cannot view the report or export', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($employee)->get(route('expenses.report'))->assertForbidden();
    $this->actingAs($employee)->get(route('expenses.export'))->assertForbidden();
});

test('CSV export streams a csv file with the filtered rows', function () {
    Expense::factory()->create(['category' => ExpenseCategory::TRAVEL, 'amount' => 42, 'expense_date' => '2026-01-10']);

    $response = $this->actingAs($this->admin)->get(route('expenses.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
});
