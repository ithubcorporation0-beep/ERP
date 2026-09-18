<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Roles::ADMIN);
});

test('creating, updating, and deleting a Customer logs the actor, subject, and changed fields', function () {
    $this->actingAs($this->admin);

    $customer = Customer::factory()->create(['name' => 'Acme Corp']);
    $created = Activity::where('subject_type', Customer::class)->where('subject_id', $customer->id)->where('event', 'created')->first();
    expect($created)->not->toBeNull()
        ->and($created->causer_id)->toBe($this->admin->id)
        ->and($created->properties['attributes']['name'])->toBe('Acme Corp');

    $customer->update(['name' => 'Acme Corporation']);
    $updated = Activity::where('subject_type', Customer::class)->where('subject_id', $customer->id)->where('event', 'updated')->first();
    expect($updated)->not->toBeNull()
        ->and($updated->properties['attributes']['name'])->toBe('Acme Corporation')
        ->and($updated->properties['old']['name'])->toBe('Acme Corp');

    $customer->delete();
    $deleted = Activity::where('subject_type', Customer::class)->where('subject_id', $customer->id)->where('event', 'deleted')->first();
    expect($deleted)->not->toBeNull()
        ->and($deleted->properties['old']['name'])->toBe('Acme Corporation');
});

test('a Project status change is captured in the update activity', function () {
    $this->actingAs($this->admin);

    $project = Project::factory()->create(['status' => ProjectStatus::PLANNED]);
    $project->update(['status' => ProjectStatus::IN_PROGRESS]);

    $activity = Activity::where('subject_type', Project::class)->where('subject_id', $project->id)->where('event', 'updated')->first();

    expect($activity->properties['old']['status'])->toBe(ProjectStatus::PLANNED->value)
        ->and($activity->properties['attributes']['status'])->toBe(ProjectStatus::IN_PROGRESS->value);
});

test('a Task status change is captured in the update activity', function () {
    $this->actingAs($this->admin);

    $task = Task::factory()->create(['status' => TaskStatus::TODO]);
    $task->update(['status' => TaskStatus::DONE]);

    $activity = Activity::where('subject_type', Task::class)->where('subject_id', $task->id)->where('event', 'updated')->first();

    expect($activity->properties['old']['status'])->toBe(TaskStatus::TODO->value)
        ->and($activity->properties['attributes']['status'])->toBe(TaskStatus::DONE->value);
});

test('Invoice, Payment, and Expense creation are logged', function () {
    $this->actingAs($this->admin);

    $invoice = Invoice::factory()->create();
    $payment = Payment::factory()->create();
    $expense = Expense::factory()->create();

    expect(Activity::where('subject_type', Invoice::class)->where('subject_id', $invoice->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(Activity::where('subject_type', Payment::class)->where('subject_id', $payment->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(Activity::where('subject_type', Expense::class)->where('subject_id', $expense->id)->where('event', 'created')->exists())->toBeTrue();
});

test('User auditing never includes the password, but does capture name/email changes', function () {
    $this->actingAs($this->admin);

    $user = User::factory()->create(['name' => 'Jane Doe', 'password' => 'secret-password']);
    $created = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->where('event', 'created')->first();

    expect($created->properties['attributes'])->not->toHaveKey('password')
        ->and($created->properties['attributes'])->not->toHaveKey('remember_token')
        ->and($created->properties['attributes']['name'])->toBe('Jane Doe');

    $user->update(['name' => 'Jane Smith']);
    $updated = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->where('event', 'updated')->first();
    expect($updated->properties['attributes']['name'])->toBe('Jane Smith')
        ->and($updated->properties['attributes'])->not->toHaveKey('password');
});

test('changing a user\'s role via the admin Users page logs an explicit role-change activity', function () {
    $target = User::factory()->create();
    $target->assignRole(Roles::EMPLOYEE);

    $this->actingAs($this->admin)
        ->patch(route('users.update-role', $target), ['role' => Roles::MANAGER])
        ->assertRedirect();

    $activity = Activity::where('subject_type', User::class)->where('subject_id', $target->id)->where('event', 'updated')->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($this->admin->id)
        ->and($activity->properties['old_role'])->toBe(Roles::EMPLOYEE)
        ->and($activity->properties['new_role'])->toBe(Roles::MANAGER)
        ->and($target->fresh()->hasRole(Roles::MANAGER))->toBeTrue();
});

test('uploading and deleting a document logs create/delete activities on the Media subject', function () {
    Storage::fake('private');

    $customer = Customer::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('documents.store', ['type' => 'customers', 'id' => $customer->id]), [
            'files' => [UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf')],
        ])
        ->assertRedirect();

    $media = $customer->fresh()->documents()->first();
    $created = Activity::where('subject_type', $media::class)->where('subject_id', $media->id)->where('event', 'created')->first();
    expect($created)->not->toBeNull()
        ->and($created->causer_id)->toBe($this->admin->id)
        ->and($created->properties['file_name'])->toBe('contract.pdf');

    $this->actingAs($this->admin)
        ->delete(route('documents.destroy', ['type' => 'customers', 'id' => $customer->id, 'media' => $media->id]))
        ->assertRedirect();

    $deleted = Activity::where('subject_type', $media::class)->where('subject_id', $media->id)->where('event', 'deleted')->first();
    expect($deleted)->not->toBeNull();
});
