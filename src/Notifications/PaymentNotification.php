<?php

namespace Takaden\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Takaden\Enums\PaymentStatus;
use Takaden\Orderable;

class PaymentNotification extends Notification
{
    use Queueable;

    public Orderable $orderable;
    public PaymentStatus $status;
    public array $payload;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Orderable $orderable, PaymentStatus $status, array $payload)
    {
        $this->orderable = $orderable;
        $this->status = $status;
        $this->payload = $payload;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title'     => 'Payment ' . $this->status?->value,
            'content'   => 'The payment of ' . $this->orderable?->currency . ' ' . $this->orderable?->amount . ' has been ' . $this->status?->value,
            'subject'   => $this->orderable?->toArray(),
            'payload'   => $this->payload,
        ];
    }
}
