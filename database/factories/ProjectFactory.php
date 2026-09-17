<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
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
            'name' => fake()->catchPhrase(),
            'code' => strtoupper(fake()->unique()->bothify('PRJ-###')),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }
}
