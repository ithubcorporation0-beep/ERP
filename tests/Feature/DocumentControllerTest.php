<?php

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Storage::fake('private');

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Roles::ADMIN);
});

test('an admin can upload a document to a customer and it appears in the list', function () {
    $customer = Customer::factory()->create();
    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    $this->actingAs($this->admin)
        ->post(route('documents.store', ['type' => 'customers', 'id' => $customer->id]), ['files' => [$file]])
        ->assertRedirect();

    expect($customer->fresh()->documents())->toHaveCount(1)
        ->and($customer->fresh()->documents()->first()->file_name)->toBe('contract.pdf');
});

test('uploading multiple files at once attaches all of them', function () {
    $customer = Customer::factory()->create();
    $files = [
        UploadedFile::fake()->create('one.pdf', 50, 'application/pdf'),
        UploadedFile::fake()->create('two.txt', 10, 'text/plain'),
    ];

    $this->actingAs($this->admin)
        ->post(route('documents.store', ['type' => 'customers', 'id' => $customer->id]), ['files' => $files])
        ->assertRedirect();

    expect($customer->fresh()->documents())->toHaveCount(2);
});

test('a disallowed file type is rejected and no document is attached', function () {
    $customer = Customer::factory()->create();
    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

    $this->actingAs($this->admin)
        ->post(route('documents.store', ['type' => 'customers', 'id' => $customer->id]), ['files' => [$file]])
        ->assertSessionHasErrors('files.0');

    expect($customer->fresh()->documents())->toHaveCount(0);
});

test('a file over 20MB is rejected', function () {
    $customer = Customer::factory()->create();
    $file = UploadedFile::fake()->create('big.pdf', 20481, 'application/pdf');

    $this->actingAs($this->admin)
        ->post(route('documents.store', ['type' => 'customers', 'id' => $customer->id]), ['files' => [$file]])
        ->assertSessionHasErrors('files.0');

    expect($customer->fresh()->documents())->toHaveCount(0);
});

test('a client can download a document on their own invoice but not on another customer\'s invoice', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownInvoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    $otherInvoice = Invoice::factory()->create(['customer_id' => $otherCustomer->id]);

    $ownMedia = $ownInvoice->addDocument(UploadedFile::fake()->create('own.pdf', 50, 'application/pdf'));
    $otherMedia = $otherInvoice->addDocument(UploadedFile::fake()->create('other.pdf', 50, 'application/pdf'));

    $this->actingAs($client)
        ->get(route('documents.download', ['type' => 'invoices', 'id' => $ownInvoice->id, 'media' => $ownMedia->id]))
        ->assertOk();

    $this->actingAs($client)
        ->get(route('documents.download', ['type' => 'invoices', 'id' => $otherInvoice->id, 'media' => $otherMedia->id]))
        ->assertForbidden();
});

test('a client cannot upload documents even to their own customer\'s invoice', function () {
    $customer = Customer::factory()->create();
    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

    $this->actingAs($client)
        ->post(route('documents.store', ['type' => 'invoices', 'id' => $invoice->id]), ['files' => [$file]])
        ->assertForbidden();

    expect($invoice->fresh()->documents())->toHaveCount(0);
});

test('an employee has no document access at all on expenses', function () {
    $employee = User::factory()->create();
    $employee->assignRole(Roles::EMPLOYEE);

    $expense = Expense::factory()->create();
    $media = $expense->addDocument(UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'));
    $file = UploadedFile::fake()->create('another.pdf', 50, 'application/pdf');

    $this->actingAs($employee)
        ->post(route('documents.store', ['type' => 'expenses', 'id' => $expense->id]), ['files' => [$file]])
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('documents.download', ['type' => 'expenses', 'id' => $expense->id, 'media' => $media->id]))
        ->assertForbidden();

    $this->actingAs($employee)
        ->delete(route('documents.destroy', ['type' => 'expenses', 'id' => $expense->id, 'media' => $media->id]))
        ->assertForbidden();
});

test('downloading a document via a mismatched entity is rejected (IDOR protection)', function () {
    $customer = Customer::factory()->create();
    $project = Project::factory()->create();

    $media = $customer->addDocument(UploadedFile::fake()->create('secret.pdf', 50, 'application/pdf'));

    $this->actingAs($this->admin)
        ->get(route('documents.download', ['type' => 'projects', 'id' => $project->id, 'media' => $media->id]))
        ->assertNotFound();
});

test('an admin can delete a document', function () {
    $customer = Customer::factory()->create();
    $media = $customer->addDocument(UploadedFile::fake()->create('old.pdf', 50, 'application/pdf'));

    $this->actingAs($this->admin)
        ->delete(route('documents.destroy', ['type' => 'customers', 'id' => $customer->id, 'media' => $media->id]))
        ->assertRedirect();

    expect(Media::find($media->id))->toBeNull();
});

test('a manager can download a payment document but cannot upload or delete one', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);

    $payment = Payment::factory()->create();
    $media = $payment->addDocument(UploadedFile::fake()->create('receipt.pdf', 50, 'application/pdf'));

    $this->actingAs($manager)
        ->get(route('documents.download', ['type' => 'payments', 'id' => $payment->id, 'media' => $media->id]))
        ->assertOk();

    $this->actingAs($manager)
        ->post(route('documents.store', ['type' => 'payments', 'id' => $payment->id]), [
            'files' => [UploadedFile::fake()->create('new.pdf', 50, 'application/pdf')],
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete(route('documents.destroy', ['type' => 'payments', 'id' => $payment->id, 'media' => $media->id]))
        ->assertForbidden();
});

test('an unknown documentable type is rejected with a 404', function () {
    $this->actingAs($this->admin)
        ->get('/widgets/1/documents/1/download')
        ->assertNotFound();
});

test('documents are stored on the private disk, not the public disk', function () {
    $customer = Customer::factory()->create();
    $media = $customer->addDocument(UploadedFile::fake()->create('private.pdf', 50, 'application/pdf'));

    expect($media->disk)->toBe('private');
    Storage::disk('private')->assertExists($media->getPathRelativeToRoot());
});
