<?php

namespace App\Http\Requests\Concerns;

trait ValidatesCustomerAddresses
{
    /**
     * Validation rules for a billing_address/shipping_address JSON field.
     *
     * @return array<string, string>
     */
    protected function addressRules(string $field): array
    {
        return [
            "{$field}" => ['nullable', 'array'],
            "{$field}.line1" => ['nullable', 'string', 'max:255'],
            "{$field}.line2" => ['nullable', 'string', 'max:255'],
            "{$field}.city" => ['nullable', 'string', 'max:255'],
            "{$field}.state" => ['nullable', 'string', 'max:255'],
            "{$field}.postal_code" => ['nullable', 'string', 'max:50'],
            "{$field}.country" => ['nullable', 'string', 'max:255'],
        ];
    }
}
