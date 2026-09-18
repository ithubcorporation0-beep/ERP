<?php

namespace App\Notifications;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Deliberately not ShouldQueue/Queueable: this app runs no queue worker,
 * so a queued notification would sit in the jobs table forever. Database
 * notifications need to land synchronously to show up immediately.
 */
class DocumentUploadedNotification extends Notification
{
    /**
     * @param  Collection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media>  $media
     */
    public function __construct(
        private readonly Model $documentable,
        private readonly string $type,
        private readonly Collection $media,
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
        $filesMessage = $this->media->count() === 1
            ? "Document \"{$this->media->first()->file_name}\" was uploaded"
            : "{$this->media->count()} documents were uploaded";

        return [
            'type' => 'document_uploaded',
            'message' => "{$filesMessage} to {$this->entityLabel()}.",
            'url' => route("{$this->type}.show", $this->documentable->id),
        ];
    }

    private function entityLabel(): string
    {
        return match (true) {
            $this->documentable instanceof Customer => "customer \"{$this->documentable->name}\"",
            $this->documentable instanceof Project => "project \"{$this->documentable->name}\"",
            $this->documentable instanceof Task => "task \"{$this->documentable->title}\"",
            $this->documentable instanceof Invoice => "invoice \"{$this->documentable->number}\"",
            $this->documentable instanceof Payment => "payment #{$this->documentable->id}",
            $this->documentable instanceof Expense => "expense #{$this->documentable->id}",
            default => 'an item',
        };
    }
}
