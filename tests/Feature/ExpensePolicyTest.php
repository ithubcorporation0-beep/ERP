<?php

use App\Models\Expense;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('viewAny is allowed for SUPER_ADMIN, ADMIN, ACCOUNTANT, MANAGER and denied for EMPLOYEE, CLIENT', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('viewAny', Expense::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::ACCOUNTANT, true],
    [Roles::MANAGER, true],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('create/update/delete are only allowed for SUPER_ADMIN, ADMIN and ACCOUNTANT, MANAGER is read-only', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $expense = Expense::factory()->create();

    expect($user->can('create', Expense::class))->toBe($expected)
        ->and($user->can('update', $expense))->toBe($expected)
        ->and($user->can('delete', $expense))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::ACCOUNTANT, true],
    [Roles::MANAGER, false],
]);

test('EMPLOYEE and CLIENT cannot view a single expense', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $expense = Expense::factory()->create();

    expect($user->can('view', $expense))->toBeFalse();
})->with([Roles::EMPLOYEE, Roles::CLIENT]);
