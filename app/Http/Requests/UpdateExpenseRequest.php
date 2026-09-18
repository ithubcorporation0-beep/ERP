<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('expense'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'project_id' => [
                'nullable',
                'exists:projects,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->filled('customer_id')
                        && ! Project::where('id', $value)->where('customer_id', $this->input('customer_id'))->exists()) {
                        $fail('The selected project does not belong to the selected customer.');
                    }
                },
            ],
            'category' => ['required', new Enum(ExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
