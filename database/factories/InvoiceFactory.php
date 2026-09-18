<?php

namespace Database\Factories;

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'project_id' => null,
            'number' => 'INV-'.fake()->unique()->numerify('####-######'),
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'currency' => 'USD',
            'status' => InvoiceStatus::DRAFT,
            'discount_type' => InvoiceDiscountType::NONE,
            'discount_value' => 0,
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance_due' => 0,
        ];
    }
}
