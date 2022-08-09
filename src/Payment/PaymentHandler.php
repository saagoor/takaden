<?php

namespace Takaden\Payment;

use Illuminate\Http\Request;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;
use Takaden\Models\Payment;
use Takaden\Notifications\PaymentNotification;
use Takaden\Orderable;
use Takaden\Payable;

abstract class PaymentHandler
{
    public PaymentProviders $name;

    abstract public function initiatePayment(Orderable $order);

    abstract public function validateSuccessfulPayment(Request $request): bool;

    public static function create(string $paymentProvider)
    {
        return PaymentProviders::from($paymentProvider)->getHandler();
    }

    public function executePayment(Request $request)
    {
        // Override this method in your payment handler class if needed
    }

    /**
     * Before creating/initiating payemnt
     */
    public function beforePaymentCreate(Request $request): void
    {
    }

    /**
     * After payment successful action
     * 1. Update payment status to 'success'.
     * 2. Mark the order as active.
     * 3. Clear cache of customer's subscription, payment & order history.
     */
    public function afterPaymentSuccessful(Request $request): Payable
    {
        $payment = $this->updateStatusAndGetPayment($request, PaymentStatus::SUCCESS);
        if ($payment->order && ! $payment->order->is_active) {
            $payment->order->is_active = true;
            $payment->order->save();
        }

        return $payment;
    }

    /**
     * After payment failed action
     * 1. Update the payment status to 'failed'.
     * 2. Mark the order as inactive.
     * 3. Clear cache of customer's subscription, payment & order history.
     */
    public function afterPaymentFailed(Request $request): Payable
    {
        $payment = $this->updateStatusAndGetPayment($request, PaymentStatus::FAILED);
        $payment->order->is_active = false;
        $payment->order->save();

        return $payment;
    }

    /**
     * After payment cancelled action
     * 1. Update the payment status to 'cancelled'.
     */
    public function afterPaymentCancelled(Request $request): Payable
    {
        return $this->updateStatusAndGetPayment($request, PaymentStatus::CANCELLED);
    }

    /**
     * Process the payload came from payment gateway,
     *  and create or update the payment record according to the payment status
     */
    protected function updateStatusAndGetPayment(Request $request, PaymentStatus $status): Payable
    {
        $paymentPayload = PayloadProcessor::process($request->all(), $this->gatewayName);
        $paymentPayload['status'] = $status;
        $payment = Payment::findOrNew($paymentPayload['payment_id']);
        $payment->update($paymentPayload);
        // Notify the customer
        $payment->customer->notify(new PaymentNotification($payment, $request->all()));

        return $payment;
    }
}
