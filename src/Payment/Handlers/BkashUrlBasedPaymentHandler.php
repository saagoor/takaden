<?php

namespace Takaden\Payment\Handlers;

use Exception;
use Illuminate\Http\Request;
use Takaden\Enums\PaymentStatus;
use Takaden\Orderable;

class BkashUrlBasedPaymentHandler extends BkashPaymentHandler
{
    public function initiatePayment(Orderable $order)
    {
        $checkout = $this->createCheckout($order);
        $payload = [
            'mode' => '0011',
            'payerReference' => $order->getTakadenCustomer()->id,
            'callbackURL' => $this->getCallbackUrl(),
            'intent' => 'sale',
            'amount' => $order->getTakadenAmount(),
            'currency' => $order->getTakadenCurrency(),
            'merchantInvoiceNumber' => $checkout->id,
        ];
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->post('/checkout/create', $payload);
        $data = $response->json();
        if ($response->failed() || ! isset($data['paymentID'])) {
            throw new Exception('Failed to initate bKash payment. '.($data['message'] ?? $data['statusMessage'] ?? 'Unknown error'));
        }
        $this->afterInitiatePayment($checkout, $data['paymentID'], $data);

        return $data;
    }

    public function getStatusFromRedirection(Request $request): PaymentStatus
    {
        return match ($request->status) {
            'initiated' => PaymentStatus::INITIATED,
            'Initiated' => PaymentStatus::INITIATED,
            'success' => PaymentStatus::SUCCESS,
            'Completed' => PaymentStatus::SUCCESS,
            'Pending Authorized' => PaymentStatus::PENDING,
            'Failed' => PaymentStatus::FAILED,
            'Aborted' => PaymentStatus::CANCELLED,
            'cancel' => PaymentStatus::CANCELLED,
            'cancelled' => PaymentStatus::CANCELLED,
            'Cancelled' => PaymentStatus::CANCELLED,
            'Declined' => PaymentStatus::FAILED,
            default => PaymentStatus::FAILED,
        };
    }

    public function validateSuccessfulPayment(Request $request): bool
    {
        $paymentId = $request->payment_id ?? $request->paymentID ?? null;
        if (! $paymentId) {
            throw new Exception('Unable to validate payment, payment ID not found.');
        }

        // Call the execute API
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->post('/checkout/execute', [
                'paymentID' => $paymentId,
            ]);
        $data = $response->json();
        if (
            $response->successful() &&
            $data && isset($data['trxID']) && isset($data['transactionStatus']) &&
            ($data['transactionStatus'] == 'Completed' || $data['transactionStatus'] == 'Authorized')
        ) {
            $request->merge($data);

            return true;
        }

        // Call the status API in case the execute API fails
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken(), '')
            ->post('/checkout/payment/status', [
                'paymentID' => $paymentId,
            ]);
        $data = $response->json();
        if (
            $response->successful() &&
            $data && isset($data['trxID']) && isset($data['transactionStatus']) &&
            ($data['transactionStatus'] == 'Completed' || $data['transactionStatus'] == 'Authorized')
        ) {
            $request->merge($data);

            return true;
        }

        return false;
    }
}
