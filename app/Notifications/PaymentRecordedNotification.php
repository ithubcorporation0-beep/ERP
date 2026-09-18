<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not ShouldQueue/Queueable: this app runs no queue worker,
 * so a queued notification would sit in the jobs table forever. Database
 * notifications need to land synchronously to show up immediately.
 */
class PaymentRecordedNotification extends Notification
{
    public function __construct(private readonly Payment $payment)
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
            'type' => 'payment_recorded',
            'message' => "A payment of {$this->payment->currency} ".number_format($this->payment->amount, 2)." was recorded for invoice \"{$this->payment->invoice->number}\".",
            'url' => route('payments.show', $this->payment),
        ];
    }
}
