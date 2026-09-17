<?php

use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('login redirects each role to its own dashboard', function (string $role, string $path) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect($path);
})->with([
    [Roles::SUPER_ADMIN, '/super'],
    [Roles::ADMIN, '/admin'],
    [Roles::MANAGER, '/manager'],
    [Roles::ACCOUNTANT, '/accountant'],
    [Roles::EMPLOYEE, '/employee'],
    [Roles::CLIENT, '/client'],
]);

test('a user with no role is redirected to the default dashboard', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
});

test('role routes reject users without the required role', function (string $path) {
    $user = User::factory()->create();
    $user->assignRole(Roles::CLIENT);

    $this->actingAs($user)->get($path)->assertForbidden();
})->with([
    '/super',
    '/admin',
    '/manager',
    '/accountant',
    '/employee',
]);

test('higher roles can access the routes below them in the hierarchy', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Roles::SUPER_ADMIN);

    foreach (['/super', '/admin', '/manager', '/accountant', '/employee'] as $path) {
        $this->actingAs($superAdmin)->get($path)->assertOk();
    }

    $this->actingAs($superAdmin)->get('/client')->assertForbidden();
});
