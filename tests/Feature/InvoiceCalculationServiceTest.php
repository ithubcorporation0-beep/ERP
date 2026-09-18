<?php

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Services\InvoiceCalculationService;

beforeEach(function () {
    $this->calculator = app(InvoiceCalculationService::class);
});

test('recalculateItem computes line subtotal, tax, and total', function () {
    $item = new InvoiceItem(['quantity' => 3, 'unit_price' => 100, 'tax_rate' => 10]);

    $this->calculator->recalculateItem($item);

    expect((float) $item->line_subtotal)->toBe(300.0)
        ->and((float) $item->line_tax)->toBe(30.0)
        ->and((float) $item->line_total)->toBe(330.0);
});

test('recalculateItem treats a null tax rate as zero', function () {
    $item = new InvoiceItem(['quantity' => 2, 'unit_price' => 50, 'tax_rate' => null]);

    $this->calculator->recalculateItem($item);

    expect((float) $item->line_tax)->toBe(0.0)
        ->and((float) $item->line_total)->toBe(100.0);
});

test('recalculateInvoice sums line items into subtotal and tax_total', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 2, 'unit_price' => 100, 'tax_rate' => 10, 'line_subtotal' => 200, 'line_tax' => 20, 'line_total' => 220]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 50, 'tax_rate' => 0, 'line_subtotal' => 50, 'line_tax' => 0, 'line_total' => 50]);

    $this->calculator->recalculateInvoice($invoice);

    expect((float) $invoice->subtotal)->toBe(250.0)
        ->and((float) $invoice->tax_total)->toBe(20.0)
        ->and((float) $invoice->total)->toBe(270.0);
});

test('a percent discount is applied to the subtotal', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'discount_type' => InvoiceDiscountType::PERCENT,
        'discount_value' => 10,
    ]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 1000, 'tax_rate' => 0, 'line_subtotal' => 1000, 'line_tax' => 0, 'line_total' => 1000]);

    $this->calculator->recalculateInvoice($invoice);

    // 1000 - 10% (100) = 900
    expect((float) $invoice->total)->toBe(900.0);
});

test('an amount discount larger than the subtotal is clamped, never producing a negative total', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'discount_type' => InvoiceDiscountType::AMOUNT,
        'discount_value' => 99999,
    ]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0, 'line_subtotal' => 100, 'line_tax' => 0, 'line_total' => 100]);

    $this->calculator->recalculateInvoice($invoice);

    expect((float) $invoice->total)->toBe(0.0);
});

test('amount_paid is derived from the sum of payments, and balance_due is clamped at zero even when overpaid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::SENT]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0, 'line_subtotal' => 100, 'line_tax' => 0, 'line_total' => 100]);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 150]);

    $this->calculator->recalculateInvoice($invoice);

    expect((float) $invoice->amount_paid)->toBe(150.0)
        // total is 100, payments sum to 150 (overpaid) -> balance_due clamped to 0, not negative
        ->and((float) $invoice->balance_due)->toBe(0.0);
});

test('status transitions from SENT to PARTIALLY_PAID to PAID as payments accumulate', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::SENT]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 200, 'tax_rate' => 0, 'line_subtotal' => 200, 'line_tax' => 0, 'line_total' => 200]);

    $this->calculator->recalculateInvoice($invoice);
    expect($invoice->status)->toBe(InvoiceStatus::SENT);

    Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 50]);
    $this->calculator->recalculateInvoice($invoice);
    expect($invoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID);

    Payment::factory()->create(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'amount' => 150]);
    $this->calculator->recalculateInvoice($invoice);
    expect($invoice->status)->toBe(InvoiceStatus::PAID);
});

test('recalculateInvoice never changes a DRAFT or VOID status', function (InvoiceStatus $status) {
    $invoice = Invoice::factory()->create(['status' => $status]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0, 'line_subtotal' => 100, 'line_tax' => 0, 'line_total' => 100]);

    $this->calculator->recalculateInvoice($invoice);

    expect($invoice->status)->toBe($status);
})->with([InvoiceStatus::DRAFT, InvoiceStatus::VOID]);

test('a negative discount_value is clamped to zero rather than inflating the total', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'discount_type' => InvoiceDiscountType::AMOUNT,
        'discount_value' => -50,
    ]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 0, 'line_subtotal' => 100, 'line_tax' => 0, 'line_total' => 100]);

    $this->calculator->recalculateInvoice($invoice);

    expect((float) $invoice->total)->toBe(100.0);
});
