<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $payment = $this->route('payment');
                    $invoice = $payment->invoice;

                    // balance_due already has this payment's current amount deducted,
                    // so add it back to get the balance available for the new amount.
                    $availableBalance = (float) $invoice->balance_due + (float) $payment->amount;

                    if ($value > $availableBalance) {
                        $fail('This payment of '.number_format($value, 2).' would exceed the balance due of '.number_format($availableBalance, 2).'.');
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
