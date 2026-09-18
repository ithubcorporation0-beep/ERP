<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not ShouldQueue/Queueable: this app runs no queue worker,
 * so a queued notification would sit in the jobs table forever. Database
 * notifications need to land synchronously to show up immediately.
 */
class InvoiceCreatedNotification extends Notification
{
    public function __construct(private readonly Invoice $invoice)
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
            'type' => 'invoice_created',
            'message' => "A new invoice \"{$this->invoice->number}\" was created for your account.",
            'url' => route('invoices.show', $this->invoice),
        ];
    }
}
