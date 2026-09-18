<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('viewAny is allowed for SUPER_ADMIN, ADMIN, ACCOUNTANT, MANAGER, CLIENT and denied for EMPLOYEE', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('viewAny', Payment::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::ACCOUNTANT, true],
    [Roles::MANAGER, true],
    [Roles::CLIENT, true],
    [Roles::EMPLOYEE, false],
]);

test('a CLIENT can only view payments belonging to their own customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownPayment = Payment::factory()->create(['customer_id' => $customer->id]);
    $otherPayment = Payment::factory()->create(['customer_id' => $otherCustomer->id]);

    expect($client->can('view', $ownPayment))->toBeTrue()
        ->and($client->can('view', $otherPayment))->toBeFalse();
});

test('create/update/delete are only allowed for ADMIN and ACCOUNTANT, MANAGER is read-only', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $payment = Payment::factory()->create();

    expect($user->can('create', Payment::class))->toBe($expected)
        ->and($user->can('update', $payment))->toBe($expected)
        ->and($user->can('delete', $payment))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::ACCOUNTANT, true],
    [Roles::MANAGER, false],
]);

test('scopeForUser returns only what each role is allowed to see', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownPayment = Payment::factory()->create(['customer_id' => $customer->id]);
    Payment::factory()->create(['customer_id' => $otherCustomer->id]);

    $policy = app(\App\Policies\PaymentPolicy::class);

    $clientIds = $policy->scopeForUser(Payment::query(), $client)->pluck('id');
    expect($clientIds)->toHaveCount(1)->and($clientIds->first())->toBe($ownPayment->id);
});
