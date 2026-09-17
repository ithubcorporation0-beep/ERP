<?php

use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('viewAdmin gate allows ADMIN and SUPER_ADMIN and denies everyone else', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect(Gate::forUser($user)->allows('viewAdmin'))->toBe($expected);
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::ACCOUNTANT, false],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('the currentRole request macro returns the highest-priority role', function () {
    $user = User::factory()->create();
    $user->assignRole([Roles::EMPLOYEE, Roles::MANAGER]);

    $this->actingAs($user)->get('/manager');

    expect(request()->currentRole())->toBe(Roles::MANAGER);
});

test('the currentRole request macro returns null for a guest', function () {
    $this->get('/');

    expect(request()->currentRole())->toBeNull();
});

test('the Users nav link and route are hidden from non-admins but visible to admins', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);

    $this->actingAs($manager)->get('/manager')
        ->assertDontSee(__('Users'));

    $this->actingAs($manager)->get('/users')->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $this->actingAs($admin)->get('/admin')
        ->assertSee(__('Users'));

    $this->actingAs($admin)->get('/users')->assertOk();
});
