<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;

test('a user can view and update (mark as read) their own notification', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $user->notify(new TaskAssignedNotification($task));
    $notification = $user->notifications()->first();

    expect($user->can('view', $notification))->toBeTrue()
        ->and($user->can('update', $notification))->toBeTrue();
});

test('a user cannot view or mark as read a notification addressed to someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $task = Task::factory()->create();

    $owner->notify(new TaskAssignedNotification($task));
    $notification = $owner->notifications()->first();

    expect($other->can('view', $notification))->toBeFalse()
        ->and($other->can('update', $notification))->toBeFalse();
});
