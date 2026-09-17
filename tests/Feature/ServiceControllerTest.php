<?php

use App\Models\Service;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('admin can list, search, create, update and delete a service', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    Service::factory()->create(['name' => 'Consulting Hour']);
    Service::factory()->create(['name' => 'Onboarding Package']);

    $this->actingAs($admin)
        ->get('/services?search=Consulting')
        ->assertOk()
        ->assertSee('Consulting Hour')
        ->assertDontSee('Onboarding Package');

    $response = $this->actingAs($admin)->post('/services', [
        'name' => 'Support Retainer',
        'unit_price' => 199.99,
        'tax_rate' => 7.5,
    ]);
    $response->assertRedirect(route('services.index'));

    $service = Service::firstWhere('name', 'Support Retainer');
    expect($service)->not->toBeNull()
        ->and((float) $service->unit_price)->toBe(199.99)
        ->and((float) $service->tax_rate)->toBe(7.5);

    $this->actingAs($admin)
        ->put(route('services.update', $service), [
            'name' => 'Support Retainer (Updated)',
            'unit_price' => 249.99,
        ])
        ->assertRedirect(route('services.index'));

    expect($service->fresh()->name)->toBe('Support Retainer (Updated)');

    $this->actingAs($admin)
        ->delete(route('services.destroy', $service))
        ->assertRedirect(route('services.index'));

    expect(Service::find($service->id))->toBeNull();
});

test('creating a service requires a unique name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);
    Service::factory()->create(['name' => 'Consulting Hour']);

    $this->actingAs($admin)
        ->post('/services', ['name' => 'Consulting Hour', 'unit_price' => 100])
        ->assertSessionHasErrors('name');
});

test('a manager can view services but cannot create, edit, or delete them', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $service = Service::factory()->create();

    $this->actingAs($manager)->get('/services')->assertOk();
    $this->actingAs($manager)->get(route('services.create'))->assertForbidden();
    $this->actingAs($manager)->post('/services', ['name' => 'New Service', 'unit_price' => 10])->assertForbidden();
    $this->actingAs($manager)->get(route('services.edit', $service))->assertForbidden();
    $this->actingAs($manager)->put(route('services.update', $service), ['name' => 'x', 'unit_price' => 10])->assertForbidden();
    $this->actingAs($manager)->delete(route('services.destroy', $service))->assertForbidden();
});

test('an employee cannot access the services module at all', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($employee)->get('/services')->assertForbidden();
});
