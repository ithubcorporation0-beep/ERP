<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
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

/**
 * A SENT invoice with a single 200 line item (total 200, balance_due 200).
 */
function sentInvoiceWithBalance(float $total = 200): Invoice
{
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::SENT, 'total' => $total, 'balance_due' => $total]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => $total,
        'tax_rate' => 0,
        'line_subtotal' => $total,
        'line_tax' => 0,
        'line_total' => $total,
    ]);

    return $invoice;
}

test('recording a payment recalculates amount_paid, balance_due, and transitions status to PARTIALLY_PAID', function () {
    $invoice = sentInvoiceWithBalance(200);

    $response = $this->actingAs($this->admin)->post('/payments', [
        'invoice_id' => $invoice->id,
        'amount' => 75,
        'currency' => 'USD',
        'method' => PaymentMethod::BANK_TRANSFER->value,
        'received_date' => now()->format('Y-m-d'),
    ]);

    $payment = Payment::first();
    $response->assertRedirect(route('payments.show', $payment));

    expect($payment->customer_id)->toBe($invoice->customer_id)
        ->and((float) $invoice->fresh()->amount_paid)->toBe(75.0)
        ->and((float) $invoice->fresh()->balance_due)->toBe(125.0)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::PARTIALLY_PAID);
});

test('a payment that exactly pays off the balance transitions the invoice to PAID', function () {
    $invoice = sentInvoiceWithBalance(200);

    $this->actingAs($this->admin)->post('/payments', [
        'invoice_id' => $invoice->id,
        'amount' => 200,
        'currency' => 'USD',
        'method' => PaymentMethod::CARD->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID)
        ->and((float) $invoice->fresh()->balance_due)->toBe(0.0);
});

test('a payment exceeding the balance due is rejected server-side', function () {
    $invoice = sentInvoiceWithBalance(200);

    $this->actingAs($this->admin)->post('/payments', [
        'invoice_id' => $invoice->id,
        'amount' => 250,
        'currency' => 'USD',
        'method' => PaymentMethod::CASH->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertSessionHasErrors('amount');

    expect(Payment::count())->toBe(0)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
});

test('a second payment that would push the total paid over the balance is rejected', function () {
    $invoice = sentInvoiceWithBalance(200);

    Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 150]);
    app(App\Services\InvoiceCalculationService::class)->recalculateInvoice($invoice);
    expect((float) $invoice->fresh()->balance_due)->toBe(50.0);

    $this->actingAs($this->admin)->post('/payments', [
        'invoice_id' => $invoice->id,
        'amount' => 60,
        'currency' => 'USD',
        'method' => PaymentMethod::CHECK->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertSessionHasErrors('amount');

    expect((float) $invoice->fresh()->balance_due)->toBe(50.0);
});

test('a payment cannot be recorded against a DRAFT or VOID invoice', function (InvoiceStatus $status) {
    $invoice = Invoice::factory()->create(['status' => $status, 'total' => 100, 'balance_due' => 100]);

    $this->actingAs($this->admin)->post('/payments', [
        'invoice_id' => $invoice->id,
        'amount' => 10,
        'currency' => 'USD',
        'method' => PaymentMethod::CASH->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertSessionHasErrors('invoice_id');
})->with([InvoiceStatus::DRAFT, InvoiceStatus::VOID]);

test('updating a payment amount re-validates overpayment against the recomputed balance', function () {
    $invoice = sentInvoiceWithBalance(200);
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 100]);
    app(App\Services\InvoiceCalculationService::class)->recalculateInvoice($invoice);
    expect((float) $invoice->fresh()->balance_due)->toBe(100.0);

    // Raising this payment to 150 is fine: available balance is 100 (current balance_due) + 100 (this payment's own amount) = 200.
    $this->actingAs($this->admin)->put(route('payments.update', $payment), [
        'amount' => 150,
        'currency' => 'USD',
        'method' => PaymentMethod::CASH->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertRedirect();

    expect((float) $payment->fresh()->amount)->toBe(150.0)
        ->and((float) $invoice->fresh()->balance_due)->toBe(50.0);

    // But raising it further to 300 would exceed the invoice total.
    $this->actingAs($this->admin)->put(route('payments.update', $payment), [
        'amount' => 300,
        'currency' => 'USD',
        'method' => PaymentMethod::CASH->value,
        'received_date' => now()->format('Y-m-d'),
    ])->assertSessionHasErrors('amount');

    expect((float) $payment->fresh()->amount)->toBe(150.0);
});

test('deleting a payment recalculates the invoice and reverts its status', function () {
    $invoice = sentInvoiceWithBalance(200);
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 200]);
    app(App\Services\InvoiceCalculationService::class)->recalculateInvoice($invoice);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);

    $this->actingAs($this->admin)
        ->delete(route('payments.destroy', $payment))
        ->assertRedirect(route('payments.index'));

    expect(Payment::find($payment->id))->toBeNull()
        ->and((float) $invoice->fresh()->amount_paid)->toBe(0.0)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
});

test('a manager can view payments but cannot create, update, or delete them', function () {
    $manager = User::factory()->create();
    $manager->assignRole(Roles::MANAGER);
    $invoice = sentInvoiceWithBalance(200);
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 50]);

    $this->actingAs($manager)->get('/payments')->assertOk();
    $this->actingAs($manager)->get(route('payments.show', $payment))->assertOk();

    $this->actingAs($manager)->get(route('payments.create'))->assertForbidden();
    $this->actingAs($manager)->post('/payments', ['invoice_id' => $invoice->id, 'amount' => 10, 'currency' => 'USD', 'method' => 'CASH', 'received_date' => now()->format('Y-m-d')])->assertForbidden();
    $this->actingAs($manager)->put(route('payments.update', $payment), ['amount' => 10, 'currency' => 'USD', 'method' => 'CASH', 'received_date' => now()->format('Y-m-d')])->assertForbidden();
    $this->actingAs($manager)->delete(route('payments.destroy', $payment))->assertForbidden();
});

test('a client can only see payments for their own customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $client = User::factory()->create(['customer_id' => $customer->id]);
    $client->assignRole(Roles::CLIENT);

    $ownInvoice = sentInvoiceWithBalance(200);
    $ownInvoice->update(['customer_id' => $customer->id]);
    $ownPayment = Payment::factory()->create(['invoice_id' => $ownInvoice->id, 'customer_id' => $customer->id, 'amount' => 50]);

    $otherPayment = Payment::factory()->create(['customer_id' => $otherCustomer->id]);

    $this->actingAs($client)->get(route('payments.show', $ownPayment))->assertOk();
    $this->actingAs($client)->get(route('payments.show', $otherPayment))->assertForbidden();
});
