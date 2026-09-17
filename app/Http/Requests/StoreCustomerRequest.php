<?php

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use App\Http\Requests\Concerns\ValidatesCustomerAddresses;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
{
    use ValidatesCustomerAddresses;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255', Rule::unique('customers', 'name')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', new Enum(CustomerStatus::class)],
            'notes' => ['nullable', 'string'],
        ], $this->addressRules('billing_address'), $this->addressRules('shipping_address'));
    }
}
