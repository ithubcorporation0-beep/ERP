<?php

namespace App\Notifications;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not ShouldQueue/Queueable: this app runs no queue worker,
 * so a queued notification would sit in the jobs table forever. Database
 * notifications need to land synchronously to show up immediately.
 */
class ProjectMemberAddedNotification extends Notification
{
    public function __construct(
        private readonly Project $project,
        private readonly ProjectMemberRole $role,
    ) {
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
            'type' => 'project_member_added',
            'message' => "You were added to project \"{$this->project->name}\" as {$this->role->label()}.",
            'url' => route('projects.show', $this->project),
        ];
    }
}
