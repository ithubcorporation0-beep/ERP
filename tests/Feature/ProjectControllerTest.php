<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Support\Roles;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->manager = User::factory()->create();
    $this->manager->assignRole(Roles::MANAGER);
});

test('manager can list, filter, create, and update a project', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    Project::factory()->create(['customer_id' => $customer->id, 'name' => 'Alpha', 'status' => ProjectStatus::IN_PROGRESS]);
    Project::factory()->create(['customer_id' => $otherCustomer->id, 'name' => 'Beta', 'status' => ProjectStatus::COMPLETED]);

    $this->actingAs($this->manager)
        ->get('/projects?customer_id='.$customer->id)
        ->assertOk()
        ->assertSee('Alpha')
        ->assertDontSee('Beta');

    $response = $this->actingAs($this->manager)->post('/projects', [
        'customer_id' => $customer->id,
        'name' => 'Gamma',
        'status' => ProjectStatus::PLANNED->value,
    ]);

    $project = Project::firstWhere('name', 'Gamma');
    $response->assertRedirect(route('projects.show', $project));

    $this->actingAs($this->manager)
        ->put(route('projects.update', $project), [
            'customer_id' => $customer->id,
            'name' => 'Gamma v2',
            'status' => ProjectStatus::IN_PROGRESS->value,
        ])
        ->assertRedirect(route('projects.show', $project));

    expect($project->fresh()->name)->toBe('Gamma v2');
});

test('an employee only sees assigned projects in the index and gets 403 on others', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $assigned = Project::factory()->create(['name' => 'Assigned Project']);
    $assigned->members()->create(['user_id' => $employee->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value]);

    $unassigned = Project::factory()->create(['name' => 'Unassigned Project']);

    $this->actingAs($employee)
        ->get('/projects')
        ->assertOk()
        ->assertSee('Assigned Project')
        ->assertDontSee('Unassigned Project');

    $this->actingAs($employee)->get(route('projects.show', $unassigned))->assertForbidden();
    $this->actingAs($employee)->get(route('projects.show', $assigned))->assertOk();
});

test('a client only sees their own customers projects', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownProject = Project::factory()->create(['customer_id' => $customer->id, 'name' => 'Own Project']);
    $otherProject = Project::factory()->create(['customer_id' => $otherCustomer->id, 'name' => 'Other Project']);

    $this->actingAs($client)
        ->get('/projects')
        ->assertOk()
        ->assertSee('Own Project')
        ->assertDontSee('Other Project');

    $this->actingAs($client)->get(route('projects.show', $ownProject))->assertOk();
    $this->actingAs($client)->get(route('projects.show', $otherProject))->assertForbidden();
});

test('the show page renders the members section with role controls for a manager', function () {
    $project = Project::factory()->create();
    $member = User::factory()->create(['name' => 'Pat Pivot']);
    $project->members()->create(['user_id' => $member->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value]);

    $this->actingAs($this->manager)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Pat Pivot');
});

test('a manager can add, re-role, and remove a project member', function () {
    $project = Project::factory()->create();
    $newMember = User::factory()->create();

    $this->actingAs($this->manager)->post(route('projects.members.store', $project), [
        'user_id' => $newMember->id,
        'role' => ProjectMemberRole::CONTRIBUTOR->value,
    ])->assertRedirect();

    $member = $project->members()->where('user_id', $newMember->id)->firstOrFail();
    expect($member->role)->toBe(ProjectMemberRole::CONTRIBUTOR);

    $this->actingAs($this->manager)
        ->patch(route('projects.members.update', [$project, $member]), ['role' => ProjectMemberRole::OWNER->value])
        ->assertRedirect();

    expect($member->fresh()->role)->toBe(ProjectMemberRole::OWNER);

    $this->actingAs($this->manager)
        ->delete(route('projects.members.destroy', [$project, $member]))
        ->assertRedirect();

    expect($project->members()->where('user_id', $newMember->id)->exists())->toBeFalse();
});

test('a CONTRIBUTOR member cannot manage other members, only an OWNER member or ADMIN+/MANAGER can', function () {
    $project = Project::factory()->create();

    $contributor = User::factory()->create();
    $contributor->assignRole(Roles::EMPLOYEE);
    $project->members()->create(['user_id' => $contributor->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value]);

    $target = User::factory()->create();

    $this->actingAs($contributor)->post(route('projects.members.store', $project), [
        'user_id' => $target->id,
        'role' => ProjectMemberRole::VIEWER->value,
    ])->assertForbidden();
});

test('only ADMIN can delete a project, not MANAGER', function () {
    $project = Project::factory()->create();

    $this->actingAs($this->manager)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Roles::ADMIN);

    $this->actingAs($admin)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'));
});
