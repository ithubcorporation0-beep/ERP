<?php

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Roles;

test('viewAny is allowed for SUPER_ADMIN, ADMIN, ACCOUNTANT, MANAGER, CLIENT and denied for EMPLOYEE', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('viewAny', Invoice::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::ACCOUNTANT, true],
    [Roles::MANAGER, true],
    [Roles::CLIENT, true],
    [Roles::EMPLOYEE, false],
]);

test('a CLIENT can only view invoices belonging to their own customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownInvoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    $otherInvoice = Invoice::factory()->create(['customer_id' => $otherCustomer->id]);

    expect($client->can('view', $ownInvoice))->toBeTrue()
        ->and($client->can('view', $otherInvoice))->toBeFalse();
});

test('create is only allowed for ADMIN and ACCOUNTANT, not MANAGER', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('create', Invoice::class))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::ACCOUNTANT, true],
    [Roles::MANAGER, false],
]);

test('a MANAGER can view invoices but never update, delete, markSent or markVoid them', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);

    expect($manager->can('view', $invoice))->toBeTrue()
        ->and($manager->can('update', $invoice))->toBeFalse()
        ->and($manager->can('delete', $invoice))->toBeFalse()
        ->and($manager->can('markSent', $invoice))->toBeFalse()
        ->and($manager->can('markVoid', $invoice))->toBeFalse();
});

test('update and manageItems are denied once an invoice is PAID or VOID', function (InvoiceStatus $status) {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);
    $invoice = Invoice::factory()->create(['status' => $status]);

    expect($admin->can('update', $invoice))->toBeFalse()
        ->and($admin->can('manageItems', $invoice))->toBeFalse();
})->with([InvoiceStatus::PAID, InvoiceStatus::VOID]);

test('markSent is only allowed while DRAFT', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $sent = Invoice::factory()->create(['status' => InvoiceStatus::SENT]);

    expect($admin->can('markSent', $draft))->toBeTrue()
        ->and($admin->can('markSent', $sent))->toBeFalse();
});

test('markVoid is denied once PAID or already VOID', function (InvoiceStatus $status) {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);
    $invoice = Invoice::factory()->create(['status' => $status]);

    expect($admin->can('markVoid', $invoice))->toBeFalse();
})->with([InvoiceStatus::PAID, InvoiceStatus::VOID]);

test('delete is only allowed for a DRAFT invoice', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $sent = Invoice::factory()->create(['status' => InvoiceStatus::SENT]);

    expect($admin->can('delete', $draft))->toBeTrue()
        ->and($admin->can('delete', $sent))->toBeFalse();
});

test('scopeForUser returns only what each role is allowed to see', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownInvoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    Invoice::factory()->create(['customer_id' => $otherCustomer->id]);

    $policy = app(\App\Policies\InvoicePolicy::class);

    $clientIds = $policy->scopeForUser(Invoice::query(), $client)->pluck('id');
    expect($clientIds)->toHaveCount(1)->and($clientIds->first())->toBe($ownInvoice->id);
});
