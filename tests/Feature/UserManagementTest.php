<?php

use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
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

test('only ADMIN/SUPER_ADMIN can access the Users pages', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(route('users.index'));

    $expected ? $response->assertOk() : $response->assertForbidden();
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::ACCOUNTANT, false],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('the index can be searched by name or email', function () {
    User::factory()->create(['name' => 'Alice Wonderland', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

    $response = $this->actingAs($this->admin)->get(route('users.index', ['search' => 'alice']));

    $response->assertOk();
    $response->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->name === 'Alice Wonderland');
});

test('the index can be filtered by role', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);

    $response = $this->actingAs($this->admin)->get(route('users.index', ['role' => Roles::EMPLOYEE]));

    $response->assertOk();
    $response->assertViewHas('users', function ($users) use ($employee) {
        return $users->contains('id', $employee->id) && $users->total() === 1;
    });
});

test('the index can be filtered by status', function () {
    $suspended = User::factory()->suspended()->create();
    User::factory()->create();

    $response = $this->actingAs($this->admin)->get(route('users.index', ['status' => UserStatus::SUSPENDED->value]));

    $response->assertOk();
    $response->assertViewHas('users', function ($users) use ($suspended) {
        return $users->total() === 1 && $users->first()->id === $suspended->id;
    });
});

test('an admin can create a user with a single role', function () {
    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'New Employee',
        'email' => 'new-employee@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => [Roles::EMPLOYEE],
    ]);

    $response->assertRedirect(route('users.index'));

    $user = User::where('email', 'new-employee@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(Roles::EMPLOYEE))->toBeTrue()
        ->and($user->customer_id)->toBeNull();
});

test('creating a CLIENT user without a customer is rejected', function () {
    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'New Client',
        'email' => 'new-client@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => [Roles::CLIENT],
    ]);

    $response->assertSessionHasErrors('customer_id');
    expect(User::where('email', 'new-client@example.com')->exists())->toBeFalse();
});

test('creating a CLIENT user with a customer links them to exactly that customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'New Client',
        'email' => 'new-client@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => [Roles::CLIENT],
        'customer_id' => $customer->id,
    ]);

    $response->assertRedirect(route('users.index'));

    $user = User::where('email', 'new-client@example.com')->first();
    expect($user->hasRole(Roles::CLIENT))->toBeTrue()
        ->and($user->customer_id)->toBe($customer->id);
});

test('an admin can assign multiple roles to a user', function () {
    $user = User::factory()->create();
    $user->assignRole(Roles::EMPLOYEE);

    $this->actingAs($this->admin)
        ->patch(route('users.update-roles', $user), ['roles' => [Roles::EMPLOYEE, Roles::MANAGER]])
        ->assertRedirect(route('users.index'));

    $fresh = $user->fresh();
    expect($fresh->hasRole(Roles::EMPLOYEE))->toBeTrue()
        ->and($fresh->hasRole(Roles::MANAGER))->toBeTrue();
});

test('the SUPER_ADMIN role cannot be removed from a user who has it', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Roles::SUPER_ADMIN);

    $response = $this->actingAs($this->admin)
        ->patch(route('users.update-roles', $superAdmin), ['roles' => [Roles::ADMIN]]);

    $response->assertSessionHasErrors('roles');
    expect($superAdmin->fresh()->hasRole(Roles::SUPER_ADMIN))->toBeTrue();
});

test('switching a user away from CLIENT clears their customer link', function () {
    $customer = Customer::factory()->create();
    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $this->actingAs($this->admin)
        ->patch(route('users.update-roles', $client), ['roles' => [Roles::EMPLOYEE]])
        ->assertRedirect();

    expect($client->fresh()->customer_id)->toBeNull()
        ->and($client->fresh()->hasRole(Roles::CLIENT))->toBeFalse();
});

test('an admin can suspend and reactivate a user', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->patch(route('users.suspend', $user))->assertRedirect();
    expect($user->fresh()->status)->toBe(UserStatus::SUSPENDED);

    $this->actingAs($this->admin)->patch(route('users.activate', $user))->assertRedirect();
    expect($user->fresh()->status)->toBe(UserStatus::ACTIVE);
});

test('a suspended user cannot log in', function () {
    $user = User::factory()->suspended()->create(['email' => 'suspended@example.com']);

    $response = $this->post('/login', [
        'email' => 'suspended@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an admin cannot delete a SUPER_ADMIN user', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Roles::SUPER_ADMIN);

    $this->actingAs($this->admin)->delete(route('users.destroy', $superAdmin))->assertRedirect();

    expect(User::find($superAdmin->id))->not->toBeNull();
});

test('a user assigned to tasks cannot be deleted without a warning', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();
    $task->assignees()->attach($user->id);

    $response = $this->actingAs($this->admin)->delete(route('users.destroy', $user));

    $response->assertRedirect();
    $response->assertSessionHas('warning');
    expect(User::find($user->id))->not->toBeNull();
});

test('a user who is a project member cannot be deleted without a warning', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->create(['user_id' => $user->id, 'role' => 'CONTRIBUTOR']);

    $response = $this->actingAs($this->admin)->delete(route('users.destroy', $user));

    $response->assertRedirect();
    $response->assertSessionHas('warning');
    expect(User::find($user->id))->not->toBeNull();
});

test('a user with no critical links can be deleted', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect(User::find($user->id))->toBeNull();
});
