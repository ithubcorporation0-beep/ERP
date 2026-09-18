<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Support\Roles;

test('a user only sees their own notifications on the index page', function () {
    $task = Task::factory()->create();
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    $me->notify(new TaskAssignedNotification($task));
    $someoneElse->notify(new TaskAssignedNotification($task));

    $response = $this->actingAs($me)->get(route('notifications.index'));

    $response->assertOk();
    $response->assertViewHas('notifications', function ($notifications) use ($me) {
        return $notifications->total() === 1
            && $notifications->first()->notifiable_id === $me->id;
    });
});

test('a notification starts unread and can be marked as read', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();
    $user->notify(new TaskAssignedNotification($task));

    $notification = $user->notifications()->first();
    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another user\'s notification as read', function () {
    $task = Task::factory()->create();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $owner->notify(new TaskAssignedNotification($task));
    $notification = $owner->notifications()->first();

    $this->actingAs($intruder)
        ->post(route('notifications.read', $notification))
        ->assertForbidden();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('mark all as read clears every unread notification for the current user, and only that user\'s', function () {
    $task = Task::factory()->create();
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    $me->notify(new TaskAssignedNotification($task));
    $me->notify(new TaskAssignedNotification($task));
    $someoneElse->notify(new TaskAssignedNotification($task));

    expect($me->unreadNotifications()->count())->toBe(2);

    $this->actingAs($me)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($me->unreadNotifications()->count())->toBe(0)
        ->and($someoneElse->unreadNotifications()->count())->toBe(1);
});

test('the notification bell shows the correct unread count', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();
    $user->notify(new TaskAssignedNotification($task));
    $user->notify(new TaskAssignedNotification($task));

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('2');
});
