<?php

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 10);
        $unitPrice = fake()->randomFloat(2, 10, 500);
        $taxRate = fake()->randomElement([0, 5, 10, 20]);
        $subtotal = round($quantity * $unitPrice, 2);
        $tax = round($subtotal * $taxRate / 100, 2);

        return [
            'invoice_id' => Invoice::factory(),
            'item_type' => InvoiceItemType::TEXT,
            'ref_id' => null,
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_subtotal' => $subtotal,
            'line_tax' => $tax,
            'line_total' => $subtotal + $tax,
        ];
    }
}
