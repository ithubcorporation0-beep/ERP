<?php

use App\Enums\CustomerStatus;
use App\Models\Customer;
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

test('admin can list customers with search and status filters', function () {
    Customer::factory()->create(['name' => 'Acme Inc', 'status' => CustomerStatus::ACTIVE]);
    Customer::factory()->create(['name' => 'Other Co', 'status' => CustomerStatus::INACTIVE]);

    $this->actingAs($this->admin)
        ->get('/customers?search=Acme')
        ->assertOk()
        ->assertSee('Acme Inc')
        ->assertDontSee('Other Co');

    $this->actingAs($this->admin)
        ->get('/customers?status=INACTIVE')
        ->assertOk()
        ->assertSee('Other Co')
        ->assertDontSee('Acme Inc');
});

test('admin can create a customer', function () {
    $payload = [
        'name' => 'Acme Inc',
        'email' => 'billing@acme.test',
        'phone' => '555-0100',
        'status' => CustomerStatus::ACTIVE->value,
        'notes' => 'VIP account',
        'billing_address' => ['line1' => '123 Main St', 'city' => 'Springfield', 'state' => 'IL', 'postal_code' => '62701', 'country' => 'US'],
        'shipping_address' => [],
    ];

    $response = $this->actingAs($this->admin)->post('/customers', $payload);

    $customer = Customer::firstWhere('name', 'Acme Inc');
    $response->assertRedirect(route('customers.show', $customer));

    expect($customer)->not->toBeNull()
        ->and($customer->status)->toBe(CustomerStatus::ACTIVE)
        ->and($customer->billing_address['city'])->toBe('Springfield');
});

test('creating a customer requires a unique name', function () {
    Customer::factory()->create(['name' => 'Acme Inc']);

    $this->actingAs($this->admin)
        ->post('/customers', [
            'name' => 'Acme Inc',
            'status' => CustomerStatus::ACTIVE->value,
        ])
        ->assertSessionHasErrors('name');
});

test('admin can view, update and delete a customer', function () {
    $customer = Customer::factory()->create(['name' => 'Acme Inc']);

    $this->actingAs($this->admin)
        ->get(route('customers.show', $customer))
        ->assertOk()
        ->assertSee('Acme Inc');

    $this->actingAs($this->admin)
        ->put(route('customers.update', $customer), [
            'name' => 'Acme Corp',
            'status' => CustomerStatus::INACTIVE->value,
        ])
        ->assertRedirect(route('customers.show', $customer));

    expect($customer->fresh()->name)->toBe('Acme Corp')
        ->and($customer->fresh()->status)->toBe(CustomerStatus::INACTIVE);

    $this->actingAs($this->admin)
        ->delete(route('customers.destroy', $customer))
        ->assertRedirect(route('customers.index'));

    expect(Customer::find($customer->id))->toBeNull();
});

test('a manager can view customers but cannot create, edit, or delete them', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $customer = Customer::factory()->create();

    $this->actingAs($manager)->get('/customers')->assertOk();
    $this->actingAs($manager)->get(route('customers.show', $customer))->assertOk();

    $this->actingAs($manager)->get(route('customers.create'))->assertForbidden();
    $this->actingAs($manager)->post('/customers', ['name' => 'New Co', 'status' => 'ACTIVE'])->assertForbidden();
    $this->actingAs($manager)->get(route('customers.edit', $customer))->assertForbidden();
    $this->actingAs($manager)->put(route('customers.update', $customer), ['name' => 'x', 'status' => 'ACTIVE'])->assertForbidden();
    $this->actingAs($manager)->delete(route('customers.destroy', $customer))->assertForbidden();
});

test('an employee cannot access the customers module at all', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($employee)->get('/customers')->assertForbidden();
});

test('the Customers nav link is visible to ADMIN, MANAGER, and ACCOUNTANT but not EMPLOYEE or CLIENT', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(\App\Support\Roles::dashboardRouteFor($user) === 'dashboard'
        ? '/dashboard'
        : route(\App\Support\Roles::dashboardRouteFor($user)));

    if ($expected) {
        $response->assertSee('Customers');
    } else {
        $response->assertDontSee(__('Customers'));
    }
})->with([
    [Roles::ADMIN, true],
    [Roles::MANAGER, true],
    [Roles::ACCOUNTANT, true],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);
