<?php

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProjectMemberRole;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DocumentUploadedNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\PaymentRecordedNotification;
use App\Notifications\ProjectMemberAddedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Roles::ADMIN);
});

test('assigning a user to a task notifies that assignee', function () {
    Notification::fake();

    $task = Task::factory()->create();
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($this->admin)
        ->post(route('tasks.assignees.store', $task), ['user_id' => $employee->id])
        ->assertRedirect();

    Notification::assertSentTo($employee, TaskAssignedNotification::class);
});

test('adding a project member notifies that user', function () {
    Notification::fake();

    $project = Project::factory()->create();
    $user = User::factory()->create();
    $user->assignRole(Roles::EMPLOYEE);

    $this->actingAs($this->admin)
        ->post(route('projects.members.store', $project), ['user_id' => $user->id, 'role' => ProjectMemberRole::CONTRIBUTOR->value])
        ->assertRedirect();

    Notification::assertSentTo($user, ProjectMemberAddedNotification::class);
});

test('creating an invoice notifies only the CLIENT users of that customer', function () {
    Notification::fake();

    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $ownClient = User::factory()->create(['customer_id' => $customer->id]);
    $ownClient->assignRole(Roles::CLIENT);

    $otherClient = User::factory()->create(['customer_id' => $otherCustomer->id]);
    $otherClient->assignRole(Roles::CLIENT);

    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $this->actingAs($this->admin)->post('/invoices', [
        'customer_id' => $customer->id,
        'issue_date' => now()->format('Y-m-d'),
        'currency' => 'USD',
        'discount_type' => InvoiceDiscountType::NONE->value,
        'discount_value' => 0,
    ])->assertRedirect();

    Notification::assertSentTo($ownClient, InvoiceCreatedNotification::class);
    Notification::assertNotSentTo($otherClient, InvoiceCreatedNotification::class);
    Notification::assertNotSentTo($employee, InvoiceCreatedNotification::class);
});

test('recording a payment notifies SUPER_ADMIN, ADMIN and ACCOUNTANT but not MANAGER, EMPLOYEE, or CLIENT', function () {
    Notification::fake();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::SENT, 'total' => 100, 'balance_due' => 100]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => 100,
        'tax_rate' => 0,
        'line_subtotal' => 100,
        'line_tax' => 0,
        'line_total' => 100,
    ]);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Roles::SUPER_ADMIN);
    $accountant = User::factory()->create();
    $accountant->assignRole(Roles::ACCOUNTANT);
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);
    $client = User::factory()->create(['customer_id' => $invoice->customer_id]);
    $client->assignRole(Roles::CLIENT);

    $this->actingAs($this->admin)->post('/payments', [
        'invoice_id' => $invoice->id,
        'amount' => 50,
        'currency' => 'USD',
        'method' => PaymentMethod::CASH->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertRedirect();

    Notification::assertSentTo($this->admin, PaymentRecordedNotification::class);
    Notification::assertSentTo($superAdmin, PaymentRecordedNotification::class);
    Notification::assertSentTo($accountant, PaymentRecordedNotification::class);
    Notification::assertNotSentTo($manager, PaymentRecordedNotification::class);
    Notification::assertNotSentTo($employee, PaymentRecordedNotification::class);
    Notification::assertNotSentTo($client, PaymentRecordedNotification::class);
});

test('uploading a document notifies project members and the customer\'s CLIENT users, but not the uploader', function () {
    Notification::fake();
    Storage::fake('private');

    $customer = Customer::factory()->create();
    $project = Project::factory()->create(['customer_id' => $customer->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $member = User::factory()->create();
    $member->assignRole(Roles::EMPLOYEE);
    $project->members()->create(['user_id' => $member->id, 'role' => ProjectMemberRole::CONTRIBUTOR]);

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $otherCustomerClient = User::factory()->create(['customer_id' => Customer::factory()->create()->id]);
    $otherCustomerClient->assignRole(Roles::CLIENT);

    $this->actingAs($this->admin)
        ->post(route('documents.store', ['type' => 'tasks', 'id' => $task->id]), [
            'files' => [UploadedFile::fake()->create('spec.pdf', 50, 'application/pdf')],
        ])
        ->assertRedirect();

    Notification::assertSentTo($member, DocumentUploadedNotification::class);
    Notification::assertSentTo($client, DocumentUploadedNotification::class);
    Notification::assertNotSentTo($otherCustomerClient, DocumentUploadedNotification::class);
    Notification::assertNotSentTo($this->admin, DocumentUploadedNotification::class);
});
