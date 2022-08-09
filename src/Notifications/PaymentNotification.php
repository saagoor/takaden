<?php

namespace Takaden\Notifications;

use Takaden\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification
{
    use Queueable;

    public Payment $payment;
    public array $payload;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Payment $payment, array $payload)
    {
        $this->payment = $payment;
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
            'title'     => 'Payment ' . $this->payment->status->value,
            'content'   => 'The payment of ' . $this->payment->currency . ' ' . $this->payment->amount . ' has been ' . $this->payment->status->value,
            'payment'   => $this->payment->toArray(),
            'payload'   => $this->payload,
        ];
    }
}
