<?php

namespace Database\Seeders;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;

class DemoProjectsSeeder extends Seeder
{
    /**
     * Seed demo customers, projects, and project members for local
     * development. Skipped outside the local environment.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Morgan Manager', 'password' => 'password', 'email_verified_at' => now()]
        );
        $manager->syncRoles([Roles::MANAGER]);

        $employee = User::firstOrCreate(
            ['email' => 'employee@example.com'],
            ['name' => 'Erin Employee', 'password' => 'password', 'email_verified_at' => now()]
        );
        $employee->syncRoles([Roles::EMPLOYEE]);

        $demoCustomer = Customer::firstOrCreate(
            ['name' => 'Acme Demo Co'],
            [
                'email' => 'contact@acme-demo.test',
                'status' => 'ACTIVE',
                'billing_address' => ['city' => 'Springfield', 'country' => 'US'],
            ]
        );

        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            ['name' => 'Casey Client', 'password' => 'password', 'email_verified_at' => now()]
        );
        $client->update(['customer_id' => $demoCustomer->id]);
        $client->syncRoles([Roles::CLIENT]);

        $otherCustomer = Customer::firstOrCreate(
            ['name' => 'Globex Demo Inc'],
            ['email' => 'contact@globex-demo.test', 'status' => 'PROSPECT']
        );

        $projects = [
            ['customer_id' => $demoCustomer->id, 'name' => 'Website Redesign', 'code' => 'DEMO-1', 'status' => ProjectStatus::IN_PROGRESS],
            ['customer_id' => $demoCustomer->id, 'name' => 'Mobile App MVP', 'code' => 'DEMO-2', 'status' => ProjectStatus::PLANNED],
            ['customer_id' => $otherCustomer->id, 'name' => 'ERP Rollout', 'code' => 'DEMO-3', 'status' => ProjectStatus::ON_HOLD],
        ];

        foreach ($projects as $attributes) {
            $project = Project::firstOrCreate(['code' => $attributes['code']], $attributes);

            $project->members()->firstOrCreate(['user_id' => $manager->id], ['role' => ProjectMemberRole::OWNER->value]);
            $project->members()->firstOrCreate(['user_id' => $employee->id], ['role' => ProjectMemberRole::CONTRIBUTOR->value]);
        }
    }
}
