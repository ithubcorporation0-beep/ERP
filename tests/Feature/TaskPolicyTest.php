<?php

use App\Enums\ProjectMemberRole;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

test('viewAny is allowed for SUPER_ADMIN, ADMIN, MANAGER, EMPLOYEE, CLIENT and denied for ACCOUNTANT', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('viewAny', Task::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, true],
    [Roles::EMPLOYEE, true],
    [Roles::CLIENT, true],
    [Roles::ACCOUNTANT, false],
]);

test('ADMIN and MANAGER can view any task', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $task = Task::factory()->create();

    expect($user->can('view', $task))->toBeTrue();
})->with([Roles::ADMIN, Roles::MANAGER]);

test('an EMPLOYEE can view a task if assigned or a member of its project, but not otherwise', function () {
    $assignee = User::factory()->create();
    $assignee->assignRole(Roles::EMPLOYEE);
    $assignedTask = Task::factory()->create();
    $assignedTask->assignees()->attach($assignee->id);

    $projectMember = User::factory()->create();
    $projectMember->assignRole(Roles::EMPLOYEE);
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $projectMember->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value]);
    $projectTask = Task::factory()->create(['project_id' => $project->id]);

    $outsider = User::factory()->create();
    $outsider->assignRole(Roles::EMPLOYEE);
    $unrelatedTask = Task::factory()->create();

    expect($assignee->can('view', $assignedTask))->toBeTrue()
        ->and($projectMember->can('view', $projectTask))->toBeTrue()
        ->and($outsider->can('view', $unrelatedTask))->toBeFalse();
});

test('a CLIENT can view (read-only) tasks under their own customers projects', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownTask = Task::factory()->create(['project_id' => Project::factory()->create(['customer_id' => $customer->id])]);
    $otherTask = Task::factory()->create(['project_id' => Project::factory()->create(['customer_id' => $otherCustomer->id])]);

    expect($client->can('view', $ownTask))->toBeTrue()
        ->and($client->can('view', $otherTask))->toBeFalse()
        ->and($client->can('update', $ownTask))->toBeFalse()
        ->and($client->can('updateStatus', $ownTask))->toBeFalse();
});

test('update (full edit) is ADMIN/MANAGER only; an assigned EMPLOYEE cannot fully update', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $assignee = User::factory()->create();
    $assignee->assignRole(Roles::EMPLOYEE);
    $task = Task::factory()->create();
    $task->assignees()->attach($assignee->id);

    expect($admin->can('update', $task))->toBeTrue()
        ->and($assignee->can('update', $task))->toBeFalse();
});

test('updateStatus is allowed for ADMIN/MANAGER and an assigned EMPLOYEE, denied for an unassigned EMPLOYEE', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);

    $assignee = User::factory()->create();
    $assignee->assignRole(Roles::EMPLOYEE);

    $unassigned = User::factory()->create();
    $unassigned->assignRole(Roles::EMPLOYEE);

    $task = Task::factory()->create();
    $task->assignees()->attach($assignee->id);

    expect($manager->can('updateStatus', $task))->toBeTrue()
        ->and($assignee->can('updateStatus', $task))->toBeTrue()
        ->and($unassigned->can('updateStatus', $task))->toBeFalse();
});

test('scopeForUser returns only the tasks each role is allowed to see', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $project = Project::factory()->create(['customer_id' => $customer->id]);
    $assignedTask = Task::factory()->create(['project_id' => $project->id]);
    $assignedTask->assignees()->attach($employee->id);

    Task::factory()->create(['project_id' => $project->id]); // same customer, not assigned to employee
    Task::factory()->create(['project_id' => Project::factory()->create(['customer_id' => $otherCustomer->id])]);

    $policy = app(\App\Policies\TaskPolicy::class);

    $employeeIds = $policy->scopeForUser(Task::query(), $employee)->pluck('id');
    expect($employeeIds)->toHaveCount(1)->and($employeeIds->first())->toBe($assignedTask->id);

    $clientIds = $policy->scopeForUser(Task::query(), $client)->pluck('id');
    expect($clientIds)->toHaveCount(2);
});
