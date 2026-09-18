<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Every user the factory default creates (this one included) gets
        // Laravel's well-known default password ("password") - fine for
        // local dev, never something to leave sitting in a real database.
        // RolesAndAdminSeeder/DemoProjectsSeeder/DemoInvoiceSeeder below
        // already guard their own demo data the same way.
        if (app()->environment('local')) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->call([
            RolesAndAdminSeeder::class,
            DemoProjectsSeeder::class,
            DemoInvoiceSeeder::class,
        ]);
    }
}
