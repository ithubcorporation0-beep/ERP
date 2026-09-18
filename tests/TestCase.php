<?php

namespace Tests;

use App\Support\Roles;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every Feature test runs against a real spatie/laravel-permission
     * install, and nearly every test in this suite assigns a role to at
     * least one user. Seeding the full role set once here means
     * individual test files no longer need their own
     * `beforeEach(fn () => ...)` role-seeding boilerplate.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (Roles::ALL as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
