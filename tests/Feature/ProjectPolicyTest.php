<?php

use App\Enums\ProjectMemberRole;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

test('viewAny is allowed for SUPER_ADMIN, ADMIN, MANAGER, EMPLOYEE, CLIENT and denied for ACCOUNTANT', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('viewAny', Project::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, true],
    [Roles::EMPLOYEE, true],
    [Roles::CLIENT, true],
    [Roles::ACCOUNTANT, false],
]);

test('ADMIN and MANAGER can view any project', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $project = Project::factory()->create();

    expect($user->can('view', $project))->toBeTrue();
})->with([Roles::ADMIN, Roles::MANAGER]);

test('an EMPLOYEE can only view projects they are a member of', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $assigned = Project::factory()->create();
    $assigned->members()->create(['user_id' => $employee->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value]);

    $unassigned = Project::factory()->create();

    expect($employee->can('view', $assigned))->toBeTrue()
        ->and($employee->can('view', $unassigned))->toBeFalse();
});

test('a CLIENT can only view projects belonging to their own customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownProject = Project::factory()->create(['customer_id' => $customer->id]);
    $otherProject = Project::factory()->create(['customer_id' => $otherCustomer->id]);

    expect($client->can('view', $ownProject))->toBeTrue()
        ->and($client->can('view', $otherProject))->toBeFalse();
});

test('scopeForUser returns only the projects each role is allowed to see', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $assigned = Project::factory()->create(['customer_id' => $customer->id]);
    $assigned->members()->create(['user_id' => $employee->id, 'role' => ProjectMemberRole::VIEWER->value]);

    // Same customer as $assigned, but the employee isn't a member of it.
    Project::factory()->create(['customer_id' => $customer->id]);
    Project::factory()->create(['customer_id' => $otherCustomer->id]);

    $policy = app(\App\Policies\ProjectPolicy::class);

    $employeeIds = $policy->scopeForUser(Project::query(), $employee)->pluck('id');
    expect($employeeIds)->toHaveCount(1)->and($employeeIds->first())->toBe($assigned->id);

    $clientIds = $policy->scopeForUser(Project::query(), $client)->pluck('id');
    expect($clientIds)->toContain($assigned->id)
        ->and($clientIds)->toHaveCount(2); // both projects for $customer
});

test('update is allowed for ADMIN, MANAGER, and a project OWNER member, denied for a CONTRIBUTOR', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $ownerEmployee = User::factory()->create();
    $ownerEmployee->assignRole(Roles::EMPLOYEE);

    $contributor = User::factory()->create();
    $contributor->assignRole(Roles::EMPLOYEE);

    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $ownerEmployee->id, 'role' => ProjectMemberRole::OWNER->value]);
    $project->members()->create(['user_id' => $contributor->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value]);

    expect($admin->can('update', $project))->toBeTrue()
        ->and($ownerEmployee->can('update', $project))->toBeTrue()
        ->and($contributor->can('update', $project))->toBeFalse();
});

test('delete is restricted to ADMIN and SUPER_ADMIN', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $project = Project::factory()->create();

    expect($user->can('delete', $project))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::EMPLOYEE, false],
]);
