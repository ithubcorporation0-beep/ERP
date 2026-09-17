<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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

    $this->manager = User::factory()->create();
    $this->manager->assignRole(Roles::MANAGER);
});

test('manager can list, filter, create, and update a task', function () {
    $project = Project::factory()->create();
    $assignee = User::factory()->create(['name' => 'Alex Assignee']);

    $todo = Task::factory()->create(['project_id' => $project->id, 'title' => 'Todo Task', 'status' => TaskStatus::TODO]);
    $done = Task::factory()->create(['project_id' => $project->id, 'title' => 'Done Task', 'status' => TaskStatus::DONE]);
    $todo->assignees()->attach($assignee->id);

    $this->actingAs($this->manager)
        ->get('/tasks?status=TODO')
        ->assertOk()
        ->assertSee('Todo Task')
        ->assertDontSee('Done Task');

    $this->actingAs($this->manager)
        ->get('/tasks?assignee_id='.$assignee->id)
        ->assertOk()
        ->assertSee('Todo Task')
        ->assertDontSee('Done Task');

    $response = $this->actingAs($this->manager)->post('/tasks', [
        'project_id' => $project->id,
        'title' => 'New Task',
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    $task = Task::firstWhere('title', 'New Task');
    $response->assertRedirect(route('tasks.show', $task));

    $this->actingAs($this->manager)
        ->put(route('tasks.update', $task), [
            'project_id' => $project->id,
            'title' => 'New Task v2',
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::URGENT->value,
        ])
        ->assertRedirect(route('tasks.show', $task));

    expect($task->fresh()->title)->toBe('New Task v2');
});

test('an employee only sees assigned or project-member tasks and gets 403 on others', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $assignedTask = Task::factory()->create(['title' => 'My Task']);
    $assignedTask->assignees()->attach($employee->id);

    $unrelatedTask = Task::factory()->create(['title' => 'Not My Task']);

    $this->actingAs($employee)
        ->get('/tasks')
        ->assertOk()
        ->assertSee('My Task')
        ->assertDontSee('Not My Task');

    $this->actingAs($employee)->get(route('tasks.show', $assignedTask))->assertOk();
    $this->actingAs($employee)->get(route('tasks.show', $unrelatedTask))->assertForbidden();
});

test('an assigned employee can update status but not fully edit the task', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $task = Task::factory()->create(['status' => TaskStatus::TODO]);
    $task->assignees()->attach($employee->id);

    $this->actingAs($employee)
        ->patch(route('tasks.status.update', $task), ['status' => TaskStatus::DONE->value])
        ->assertRedirect();

    expect($task->fresh()->status)->toBe(TaskStatus::DONE);

    $this->actingAs($employee)->get(route('tasks.edit', $task))->assertForbidden();
    $this->actingAs($employee)->put(route('tasks.update', $task), [
        'project_id' => $task->project_id,
        'title' => 'Hijacked',
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::LOW->value,
    ])->assertForbidden();
});

test('a client can view but never update a task under their customer', function () {
    $customer = Customer::factory()->create();
    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $task = Task::factory()->create(['project_id' => Project::factory()->create(['customer_id' => $customer->id])]);

    $this->actingAs($client)->get(route('tasks.show', $task))->assertOk();
    $this->actingAs($client)->patch(route('tasks.status.update', $task), ['status' => TaskStatus::DONE->value])->assertForbidden();
});

test('a manager can add and remove a task assignee', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($this->manager)
        ->post(route('tasks.assignees.store', $task), ['user_id' => $user->id])
        ->assertRedirect();

    expect($task->assignees()->whereKey($user->id)->exists())->toBeTrue();

    $this->actingAs($this->manager)
        ->delete(route('tasks.assignees.destroy', [$task, $user]))
        ->assertRedirect();

    expect($task->assignees()->whereKey($user->id)->exists())->toBeFalse();
});

test('the New Task button on a project show page links to the task create form with the project preselected', function () {
    $project = Project::factory()->create();

    $response = $this->actingAs($this->manager)->get(route('projects.show', $project));

    $response->assertOk()->assertSee(route('tasks.create', ['project_id' => $project->id]), false);
});

test('only ADMIN/MANAGER can delete a task', function () {
    $task = Task::factory()->create();

    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);
    $task->assignees()->attach($employee->id);

    $this->actingAs($employee)->delete(route('tasks.destroy', $task))->assertForbidden();

    $this->actingAs($this->manager)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect(route('tasks.index'));

    expect(Task::find($task->id))->toBeNull();
});
