<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'project_id' => null,
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'expense_date' => now()->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
