<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Callers that care about the invoice/customer matching (e.g. any
     * test exercising overpayment or status transitions) should pass
     * both invoice_id and customer_id explicitly.
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'customer_id' => Customer::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference' => fake()->optional()->bothify('REF-####'),
            'received_date' => now()->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
