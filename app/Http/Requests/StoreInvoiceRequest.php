<?php

namespace App\Http\Requests;

use App\Enums\InvoiceDiscountType;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'project_id' => [
                'nullable',
                'exists:projects,id',
                function ($attribute, $value, $fail) {
                    if ($value && ! Project::where('id', $value)->where('customer_id', $this->input('customer_id'))->exists()) {
                        $fail('The selected project does not belong to the selected customer.');
                    }
                },
            ],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'size:3'],
            'discount_type' => ['required', new Enum(InvoiceDiscountType::class)],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === InvoiceDiscountType::PERCENT->value && $value > 100) {
                        $fail('A percentage discount cannot exceed 100.');
                    }
                },
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
