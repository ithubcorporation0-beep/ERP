<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not ShouldQueue/Queueable: this app runs no queue worker,
 * so a queued notification would sit in the jobs table forever. Database
 * notifications need to land synchronously to show up immediately.
 */
class TaskAssignedNotification extends Notification
{
    public function __construct(private readonly Task $task)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'message' => "You were assigned to task \"{$this->task->title}\" in project \"{$this->task->project->name}\".",
            'url' => route('tasks.show', $this->task),
        ];
    }
}
