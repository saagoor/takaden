<?php

namespace Takaden\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;
use Takaden\Models\Checkout;
use Takaden\Notifications\PaymentNotification;
use Takaden\Orderable;

abstract class PaymentHandler
{
    public PaymentProviders $providerName;

    abstract public function initiatePayment(Orderable $order);

    abstract public function validateSuccessfulPayment(Request $request): bool;

    public function getStatusFromRedirection(Request $request): PaymentStatus
    {
        return PaymentStatus::INITIATED;
    }

    public static function create(string|PaymentProviders $paymentProvider)
    {
        if ($paymentProvider instanceof PaymentProviders) {
            return $paymentProvider->getHandler();
        }

        return PaymentProviders::from($paymentProvider)->getHandler();
    }

    protected function createCheckout(Orderable $order): Checkout
    {
        return Checkout::create([
            'orderable_id' => $order->getKey(),
            'orderable_type' => $order::class,
            'amount' => $order->getTakadenAmount(),
            'currency' => $order->getTakadenCurrency(),
            'payment_provider' => $this->providerName,
        ]);
    }

    public function executePayment(Request $request): bool
    {
        // Override this method in your payment handler class if needed
        return false;
    }

    /**
     * Before creating/initiating payemnt
     */
    public function beforeInitiatePayment(Request $request): void
    {
    }

    /**
     * After payment initiate payment
     */
    public function afterInitiatePayment(Checkout $checkout, string $providersPaymentId, array $responsePayload): void
    {
        $checkout->update([
            'providers_payment_id' => $providersPaymentId,
            'payload' => $responsePayload,
        ]);
    }

    /**
     * After payment successful action
     * 1. Update payment status to 'success'.
     * 2. Mark the order as active.
     * 3. Clear cache of customer's subscription, payment & order history.
     */
    public function afterPaymentSuccessful(Request $request): Orderable
    {
        $paymentPayload = PayloadProcessor::process($request->all(), $this->providerName);
        $checkout = Checkout::findOrFail($paymentPayload['takaden_id']);
        $checkout->update([
            'payment_provider' => $this->providerName,
            'payment_status' => PaymentStatus::SUCCESS,
            'payload' => $paymentPayload,
        ]);
        $checkout->orderable->handleSuccessPayment($paymentPayload);
        Notification::send(
            notifiables: $checkout->orderable->getTakadenNotifiables(),
            notification: new PaymentNotification($checkout->orderable, PaymentStatus::SUCCESS, $paymentPayload),
        );

        return $checkout->orderable;
    }

    /**
     * After payment failed action
     * 1. Update the payment status to 'failed'.
     * 2. Mark the order as inactive.
     * 3. Clear cache of customer's subscription, payment & order history.
     */
    public function afterPaymentFailed(Request $request): Orderable
    {
        $paymentPayload = PayloadProcessor::process($request->all(), $this->providerName);
        $checkout = Checkout::findOrFail($paymentPayload['takaden_id']);
        $checkout->update([
            'payment_provider' => $this->providerName,
            'payment_status' => PaymentStatus::FAILED,
            'payload' => $paymentPayload,
        ]);
        $checkout->orderable->handleFailPayment($paymentPayload);
        Notification::send(
            notifiables: $checkout->orderable->getTakadenNotifiables(),
            notification: new PaymentNotification($checkout->orderable, PaymentStatus::FAILED, $paymentPayload),
        );

        return $checkout->orderable;
    }

    /**
     * After payment cancelled action
     * 1. Update the payment status to 'cancelled'.
     */
    public function afterPaymentCancelled(Request $request): Orderable
    {
        $paymentPayload = PayloadProcessor::process($request->all(), $this->providerName);
        $checkout = Checkout::findOrFail($paymentPayload['takaden_id']);
        $checkout->update([
            'payment_provider' => $this->providerName,
            'payment_status' => PaymentStatus::CANCELLED,
            'payload' => $paymentPayload,
        ]);
        $checkout->orderable->handleCancelPayment($paymentPayload);
        Notification::send(
            notifiables: $checkout->orderable->getTakadenNotifiables(),
            notification: new PaymentNotification($checkout->orderable, PaymentStatus::CANCELLED, $paymentPayload),
        );

        return $checkout->orderable;
    }
}
