<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'billing_address' => [
                'line1' => fake()->streetAddress(),
                'line2' => null,
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->country(),
            ],
            'shipping_address' => null,
            'status' => fake()->randomElement(CustomerStatus::cases()),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
