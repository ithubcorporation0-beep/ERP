<?php

use App\Models\User;
use App\Support\Roles;

test('viewAny, create, update, and delete are ADMIN+ only', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $target = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBe($expected)
        ->and($user->can('create', User::class))->toBe($expected)
        ->and($user->can('update', $target))->toBe($expected)
        ->and($user->can('delete', $target))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::ACCOUNTANT, false],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('view is allowed for ADMIN+ on any user, including themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);
    $someoneElse = User::factory()->create();

    expect($admin->can('view', $admin))->toBeTrue()
        ->and($admin->can('view', $someoneElse))->toBeTrue();
});

test('a non-admin cannot view any user record, including their own', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    expect($employee->can('view', $employee))->toBeFalse();
});
