<?php

use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (Roles::ALL as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    Storage::fake('private');
});

test('a user has no avatar by default and requesting it returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.avatar', $user))
        ->assertNotFound();
});

test('a user can upload an avatar and then view it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar.store'), [
            'avatar' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ])
        ->assertRedirect();

    expect($user->fresh()->avatar())->not->toBeNull()
        ->and($user->fresh()->avatar()->disk)->toBe('private');

    $this->actingAs($user)
        ->get(route('users.avatar', $user))
        ->assertOk();
});

test('uploading a new avatar replaces the old one (single file collection)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.avatar.store'), [
        'avatar' => UploadedFile::fake()->image('first.jpg', 200, 200),
    ]);
    $firstId = $user->fresh()->avatar()->id;

    $this->actingAs($user)->post(route('profile.avatar.store'), [
        'avatar' => UploadedFile::fake()->image('second.jpg', 200, 200),
    ]);

    $fresh = $user->fresh();
    expect(\Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'avatar')->where('model_id', $user->id)->count())->toBe(1)
        ->and($fresh->avatar()->id)->not->toBe($firstId);
});

test('a non-image file is rejected as an avatar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.avatar.store'), [
            'avatar' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar())->toBeNull();
});

test('a user can remove their avatar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.avatar.store'), [
        'avatar' => UploadedFile::fake()->image('me.jpg', 200, 200),
    ]);
    expect($user->fresh()->avatar())->not->toBeNull();

    $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect();

    expect($user->fresh()->avatar())->toBeNull();
});

test('any authenticated user can view another user\'s avatar', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();

    $this->actingAs($owner)->post(route('profile.avatar.store'), [
        'avatar' => UploadedFile::fake()->image('me.jpg', 200, 200),
    ]);

    $this->actingAs($viewer)
        ->get(route('users.avatar', $owner))
        ->assertOk();
});
