<?php

use App\Models\Customer;
use App\Models\Project;
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

test('only ADMIN/SUPER_ADMIN can view the audit log page', function (string $role, bool $expected) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(route('audit-log.index'));

    $expected ? $response->assertOk() : $response->assertForbidden();
})->with([
    [Roles::SUPER_ADMIN, true],
    [Roles::ADMIN, true],
    [Roles::MANAGER, false],
    [Roles::ACCOUNTANT, false],
    [Roles::EMPLOYEE, false],
    [Roles::CLIENT, false],
]);

test('the entity filter narrows results to the selected subject type', function () {
    $this->actingAs($this->admin);

    $customer = Customer::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($this->admin)->get(route('audit-log.index', ['subject_type' => Customer::class]));

    $response->assertOk();
    $response->assertViewHas('activities', function ($activities) {
        return $activities->total() >= 1
            && $activities->every(fn ($activity) => $activity->subject_type === Customer::class);
    });
});

test('the actor filter narrows results to the selected causer', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(Roles::ADMIN);

    $this->actingAs($this->admin);
    Customer::factory()->create(['name' => 'By Admin One']);

    $this->actingAs($otherAdmin);
    Customer::factory()->create(['name' => 'By Admin Two']);

    $response = $this->actingAs($this->admin)->get(route('audit-log.index', ['causer_id' => $otherAdmin->id]));

    $response->assertOk();
    $response->assertViewHas('activities', function ($activities) use ($otherAdmin) {
        return $activities->total() >= 1
            && $activities->every(fn ($activity) => $activity->causer_id === $otherAdmin->id);
    });
});

test('the action filter narrows results to the selected event', function () {
    $this->actingAs($this->admin);

    $customer = Customer::factory()->create();
    $customer->update(['name' => 'Updated Name']);

    $response = $this->actingAs($this->admin)->get(route('audit-log.index', ['event' => 'updated']));

    $response->assertOk();
    $response->assertViewHas('activities', function ($activities) {
        return $activities->total() >= 1
            && $activities->every(fn ($activity) => $activity->event === 'updated');
    });
});

test('the date range filter excludes activity outside the range', function () {
    $this->actingAs($this->admin);

    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->admin)->get(route('audit-log.index', [
        'date_from' => now()->addDay()->format('Y-m-d'),
        'date_to' => now()->addDays(2)->format('Y-m-d'),
    ]));

    $response->assertOk();
    $response->assertViewHas('activities', fn ($activities) => $activities->total() === 0);
});
