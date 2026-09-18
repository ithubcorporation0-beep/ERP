<?php

use App\Models\User;
use App\Support\Roles;

test('admin-only nav items are hidden from a non-admin role', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $response = $this->actingAs($employee)->get('/dashboard');

    $response->assertOk();
    $response->assertDontSee('Users');
    $response->assertDontSee('Audit Log');
    $response->assertDontSee(route('settings.edit'), false);
});

test('admin-only nav items are visible to an admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Users');
    $response->assertSee('Audit Log');
    $response->assertSee(route('settings.edit'), false);
});

test('a CLIENT sees no internal-ops nav items they lack a policy for', function () {
    $customer = \App\Models\Customer::factory()->create();
    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $response = $this->actingAs($client)->get('/dashboard');

    $response->assertOk();
    $response->assertDontSee('Users');
    $response->assertDontSee('Audit Log');
    $response->assertDontSee(route('settings.edit'), false);
});

test('the customer search input auto-submits on a debounce for a faster filter experience', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $response = $this->actingAs($admin)->get('/customers');

    $response->assertOk();
    $response->assertSee('x-on:input.debounce.400ms', false);
});
