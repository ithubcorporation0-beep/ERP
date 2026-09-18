<?php

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
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

test('admin can create an invoice, which gets a generated number and DRAFT status', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->admin)->post('/invoices', [
        'customer_id' => $customer->id,
        'issue_date' => now()->format('Y-m-d'),
        'currency' => 'USD',
        'discount_type' => 'NONE',
        'discount_value' => 0,
    ]);

    $invoice = Invoice::first();
    $response->assertRedirect(route('invoices.show', $invoice));

    expect($invoice->number)->toStartWith('INV-'.now()->year.'-')
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT);
});

test('a project belonging to a different customer is rejected', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $project = Project::factory()->create(['customer_id' => $otherCustomer->id]);

    $this->actingAs($this->admin)->post('/invoices', [
        'customer_id' => $customer->id,
        'project_id' => $project->id,
        'issue_date' => now()->format('Y-m-d'),
        'currency' => 'USD',
        'discount_type' => 'NONE',
        'discount_value' => 0,
    ])->assertSessionHasErrors('project_id');
});

test('adding, updating, and removing a line item recalculates the invoice totals', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);

    $this->actingAs($this->admin)->post(route('invoices.items.store', $invoice), [
        'item_type' => InvoiceItemType::TEXT->value,
        'description' => 'Consulting',
        'quantity' => 2,
        'unit_price' => 100,
        'tax_rate' => 10,
    ])->assertRedirect();

    $invoice->refresh();
    expect((float) $invoice->total)->toBe(220.0);

    $item = $invoice->items()->first();

    $this->actingAs($this->admin)->patch(route('invoices.items.update', [$invoice, $item]), [
        'item_type' => InvoiceItemType::TEXT->value,
        'description' => 'Consulting',
        'quantity' => 4,
        'unit_price' => 100,
        'tax_rate' => 10,
    ])->assertRedirect();

    expect((float) $invoice->fresh()->total)->toBe(440.0);

    $this->actingAs($this->admin)->delete(route('invoices.items.destroy', [$invoice, $item]))
        ->assertRedirect();

    expect((float) $invoice->fresh()->total)->toBe(0.0);
});

test('markSent transitions a DRAFT invoice to SENT, and markVoid voids it', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);

    $this->actingAs($this->admin)->post(route('invoices.mark-sent', $invoice))->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);

    $this->actingAs($this->admin)->post(route('invoices.mark-void', $invoice))->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::VOID);
});

test('a VOID invoice cannot be edited, have items changed, or be marked sent again', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::VOID]);

    $this->actingAs($this->admin)->get(route('invoices.edit', $invoice))->assertForbidden();
    $this->actingAs($this->admin)->post(route('invoices.mark-sent', $invoice))->assertForbidden();
    $this->actingAs($this->admin)->post(route('invoices.items.store', $invoice), [
        'item_type' => 'TEXT', 'description' => 'x', 'quantity' => 1, 'unit_price' => 1,
    ])->assertForbidden();
});

test('a manager can view invoices and print them but cannot create, edit, or act on them', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);

    $this->actingAs($manager)->get('/invoices')->assertOk();
    $this->actingAs($manager)->get(route('invoices.show', $invoice))->assertOk();
    $this->actingAs($manager)->get(route('invoices.print', $invoice))->assertOk();

    $this->actingAs($manager)->get(route('invoices.create'))->assertForbidden();
    $this->actingAs($manager)->post(route('invoices.mark-sent', $invoice))->assertForbidden();
});

test('a client can only see their own customers invoices in the index and gets 403 on others', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownInvoice = Invoice::factory()->create(['customer_id' => $customer->id, 'number' => 'INV-OWN']);
    $otherInvoice = Invoice::factory()->create(['customer_id' => $otherCustomer->id, 'number' => 'INV-OTHER']);

    $this->actingAs($client)
        ->get('/invoices')
        ->assertOk()
        ->assertSee('INV-OWN')
        ->assertDontSee('INV-OTHER');

    $this->actingAs($client)->get(route('invoices.show', $ownInvoice))->assertOk();
    $this->actingAs($client)->get(route('invoices.show', $otherInvoice))->assertForbidden();
    $this->actingAs($client)->post(route('invoices.mark-sent', $ownInvoice))->assertForbidden();
});

test('destroy is only reachable for a DRAFT invoice', function () {
    $sent = Invoice::factory()->create(['status' => InvoiceStatus::SENT]);
    $this->actingAs($this->admin)->delete(route('invoices.destroy', $sent))->assertForbidden();

    $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $this->actingAs($this->admin)->delete(route('invoices.destroy', $draft))->assertRedirect(route('invoices.index'));
    expect(Invoice::find($draft->id))->toBeNull();
});

test('the show page lists recorded payments and offers a Record Payment link while eligible', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::SENT, 'total' => 100, 'balance_due' => 100]);

    $this->actingAs($this->admin)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee(route('payments.create', ['invoice_id' => $invoice->id]), false)
        ->assertSee('No payments recorded yet.');

    \App\Models\Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 40, 'reference' => 'WIRE-123']);
    app(\App\Services\InvoiceCalculationService::class)->recalculateInvoice($invoice);

    $this->actingAs($this->admin)
        ->get(route('invoices.show', $invoice))
        ->assertOk()
        ->assertSee('WIRE-123');
});
