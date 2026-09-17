<?php

use App\Models\Customer;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('viewAny is allowed for SUPER_ADMIN, ADMIN, MANAGER, ACCOUNTANT and denied for EMPLOYEE and CLIENT', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('viewAny', Customer::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, true],
    [Roles::ACCOUNTANT, true],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('create, update and delete are only allowed for ADMIN and SUPER_ADMIN', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $customer = Customer::factory()->create();

    expect($user->can('create', Customer::class))->toBe($expected)
        ->and($user->can('update', $customer))->toBe($expected)
        ->and($user->can('delete', $customer))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::ACCOUNTANT, false],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('a CLIENT can view only their own linked customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    expect($client->can('view', $customer))->toBeTrue()
        ->and($client->can('view', $otherCustomer))->toBeFalse();
});
