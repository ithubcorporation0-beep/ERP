<?php

/**
 * The Breeze scaffolding already has its own login/register/password
 * flow tests under tests/Feature/Auth, and RoleAccessTest.php covers
 * post-login role redirects and role-gated route access in depth. What's
 * missing is the simplest auth check of all: that every module's
 * protected routes actually require authentication in the first place,
 * for a guest who isn't logged in at all.
 */
test('a guest is redirected to login when visiting a protected route', function (string $path) {
    $this->get($path)->assertRedirect('/login');
})->with([
    '/dashboard',
    '/customers',
    '/projects',
    '/tasks',
    '/services',
    '/products',
    '/invoices',
    '/payments',
    '/expenses',
    '/notifications',
    '/users',
    '/audit-log',
    '/settings',
    '/profile',
    '/super',
    '/admin',
    '/manager',
    '/accountant',
    '/employee',
    '/client',
]);

test('a guest cannot submit a state-changing request to a protected route', function () {
    $this->post('/customers', [])->assertRedirect('/login');
    $this->post('/logout')->assertRedirect('/login');
});

test('the login page itself is reachable by a guest', function () {
    $this->get('/login')->assertOk();
});

test('an authenticated user is redirected away from the guest-only login page', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
});
