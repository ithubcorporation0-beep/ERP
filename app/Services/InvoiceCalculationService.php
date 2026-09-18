<?php

namespace App\Services;

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceCalculationService
{
    /**
     * Recompute a single line item's subtotal/tax/total from its
     * quantity, unit price, and tax rate. Does not save the item.
     */
    public function recalculateItem(InvoiceItem $item): InvoiceItem
    {
        $quantity = max(0, (float) $item->quantity);
        $unitPrice = max(0, (float) $item->unit_price);
        $taxRate = $item->tax_rate !== null ? max(0, (float) $item->tax_rate) : 0.0;

        $subtotal = round($quantity * $unitPrice, 2);
        $tax = round($subtotal * $taxRate / 100, 2);

        $item->line_subtotal = number_format($subtotal, 2, '.', '');
        $item->line_tax = number_format($tax, 2, '.', '');
        $item->line_total = number_format($subtotal + $tax, 2, '.', '');

        return $item;
    }

    /**
     * Recompute an invoice's subtotal, tax_total, total, amount_paid,
     * and balance_due. amount_paid is derived from the sum of the
     * invoice's payments (the Payments module is the source of truth
     * for it, not a manually-entered field). Applies the discount and
     * clamps every total at zero. Also resolves the payment-related
     * status (SENT / PARTIALLY_PAID / PAID) from the new balance_due —
     * DRAFT and VOID are left untouched, since those only change via an
     * explicit action. Saves the invoice.
     */
    public function recalculateInvoice(Invoice $invoice): Invoice
    {
        $invoice->load(['items', 'payments']);

        $subtotal = round((float) $invoice->items->sum(fn (InvoiceItem $item) => (float) $item->line_subtotal), 2);
        $taxTotal = round((float) $invoice->items->sum(fn (InvoiceItem $item) => (float) $item->line_tax), 2);

        $discount = $this->discountAmount($invoice, $subtotal);

        $total = max(0.0, round($subtotal - $discount + $taxTotal, 2));

        $amountPaid = max(0.0, round((float) $invoice->payments->sum(fn ($payment) => (float) $payment->amount), 2));
        $balanceDue = max(0.0, round($total - $amountPaid, 2));

        $invoice->subtotal = number_format($subtotal, 2, '.', '');
        $invoice->tax_total = number_format($taxTotal, 2, '.', '');
        $invoice->total = number_format($total, 2, '.', '');
        $invoice->amount_paid = number_format($amountPaid, 2, '.', '');
        $invoice->balance_due = number_format($balanceDue, 2, '.', '');
        $invoice->status = $this->resolveStatus($invoice->status, $total, $balanceDue);

        $invoice->save();

        return $invoice;
    }

    /**
     * The discount amount in currency units, never more than the
     * subtotal (so a discount can never push the total negative).
     */
    protected function discountAmount(Invoice $invoice, float $subtotal): float
    {
        $value = max(0.0, (float) $invoice->discount_value);

        $discount = match ($invoice->discount_type) {
            InvoiceDiscountType::PERCENT => $subtotal * min($value, 100) / 100,
            InvoiceDiscountType::AMOUNT => $value,
            InvoiceDiscountType::NONE => 0.0,
        };

        return round(min($discount, $subtotal), 2);
    }

    protected function resolveStatus(InvoiceStatus $current, float $total, float $balanceDue): InvoiceStatus
    {
        if (in_array($current, [InvoiceStatus::DRAFT, InvoiceStatus::VOID], true)) {
            return $current;
        }

        if ($total > 0 && $balanceDue <= 0) {
            return InvoiceStatus::PAID;
        }

        if ($balanceDue > 0 && $balanceDue < $total) {
            return InvoiceStatus::PARTIALLY_PAID;
        }

        return InvoiceStatus::SENT;
    }
}
