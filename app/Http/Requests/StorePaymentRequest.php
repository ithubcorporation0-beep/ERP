<?php

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => [
                'required',
                'exists:invoices,id',
                function ($attribute, $value, $fail) {
                    $invoice = Invoice::find($value);

                    if ($invoice && ! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID], true)) {
                        $fail('Payments can only be recorded against a SENT or PARTIALLY_PAID invoice.');
                    }
                },
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $invoice = Invoice::find($this->input('invoice_id'));

                    if ($invoice && $value > (float) $invoice->balance_due) {
                        $fail('This payment of '.number_format($value, 2).' would exceed the balance due of '.number_format($invoice->balance_due, 2).'.');
                    }
                },
            ],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', new Enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
