<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Seed the ERP's roles and, in local environments only, an initial
     * SUPER_ADMIN user for development.
     */
    public function run(): void
    {
        foreach (Roles::ALL as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        if (! app()->environment('local')) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole(Roles::SUPER_ADMIN);
    }
}
