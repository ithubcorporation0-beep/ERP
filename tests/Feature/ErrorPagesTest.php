<?php

use App\Models\Customer;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    // Laravel only renders resources/views/errors/*.blade.php instead of
    // the Whoops/Ignition debug page when app.debug is off, matching how
    // these pages actually behave in production.
    config(['app.debug' => false]);
});

test('a 404 shows the themed error page with a link back to the dashboard for an authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole(Roles::EMPLOYEE);

    $response = $this->actingAs($user)->get('/customers/999999');

    $response->assertNotFound();
    $response->assertSee('404');
    $response->assertSee('Back to Dashboard');
});

test('a 404 for a guest links back to login instead of a dashboard', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertSee('404');
    $response->assertSee('Back to Login');
});

test('a 403 shows the themed error page', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $response = $this->actingAs($employee)->get('/settings');

    $response->assertForbidden();
    $response->assertSee('403');
    $response->assertSee('Back to Dashboard');
});

test('a validation failure surfaces a global error summary banner on the next page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $response = $this->actingAs($admin)->from('/customers/create')->post('/customers', []);

    $response->assertRedirect('/customers/create');
    $response->assertSessionHasErrors(['name', 'status']);

    $this->actingAs($admin)->get('/customers/create')
        ->assertSee('Please fix the following before continuing');
});

test('customer index paginates 20 per page by default', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    Customer::factory()->count(25)->create();

    $response = $this->actingAs($admin)->get('/customers');

    $response->assertOk();
    $response->assertViewHas('customers', fn ($paginator) => $paginator->perPage() === 20 && $paginator->count() === 20);
});
