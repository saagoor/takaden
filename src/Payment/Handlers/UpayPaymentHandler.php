<?php

namespace Takaden\Payment\Handlers;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;
use Takaden\Orderable;
use Takaden\Payment\PaymentHandler;

class UpayPaymentHandler extends PaymentHandler
{
    public PaymentProviders $providerName = PaymentProviders::UPAY;

    protected array $config;

    public function __construct()
    {
        $this->config = [
            'base_url' => config('takaden.providers.upay.base_url'),
            'merchant_id' => config('takaden.providers.upay.merchant_id'),
            'merchant_key' => config('takaden.providers.upay.merchant_key'),
            'merchant_code' => config('takaden.providers.upay.merchant_code'),
            'merchant_name' => config('takaden.providers.upay.merchant_name'),
            'merchant_mobile' => config('takaden.providers.upay.merchant_mobile'),
            'merchant_country' => config('takaden.providers.upay.merchant_country'),
            'merchant_city' => config('takaden.providers.upay.merchant_city'),
        ];
        if (!$this->config['base_url'] || !$this->config['merchant_id'] || !$this->config['merchant_key'] || !$this->config['merchant_code'] || !$this->config['merchant_name']) {
            throw new Exception('Upay credentials not found, make sure to add upay base url, merchant id, merchant key, merchant code & merchant name on the .env file.');
        }
    }

    public function initiatePayment(Orderable $order)
    {
        $checkout = $this->createCheckout($order);
        $response = $this->httpClient()
            ->withToken($this->getAuthToken(), 'UPAY')
            ->post('/payment/merchant-payment-init/', [
                'date' => date('Y-m-d'),
                'txn_id' => $checkout->id,
                'invoice_id' => $checkout->id,
                'amount' => $order->getTakadenAmount(),
                'merchant_id' => $this->config['merchant_id'],
                'merchant_name' => $this->config['merchant_name'],
                'merchant_code' => $this->config['merchant_code'],
                'merchant_country_code' => $this->config['merchant_country'],
                'merchant_city' => $this->config['merchant_city'],
                'merchant_category_code' => $this->config['merchant_code'],
                'merchant_mobile' => $this->config['merchant_mobile'],
                'transaction_currency_code' => $order->getTakadenCurrency(),
                'redirect_url' => route('takaden.checkout.redirection', $this->providerName),
            ]);
        if ($response->successful() && $data = $response->json('data')) {
            $this->afterInitiatePayment($checkout, $data['trx_id'], $data);

            return $data['gateway_url'];
        }
        throw new Exception($response->json('message', 'Something went wrong') . '. Unable to initiate payment with upay.');
    }

    public function validateSuccessfulPayment(Request $request): bool
    {
        $response = $this->httpClient()
            ->withToken($this->getAuthToken(), 'UPAY')
            ->get('/payment/single-payment-status/' . $request->invoice_id);
        if ($response->successful() && $data = $response->json('data')) {
            return $data['status'] === 'success';
        }

        return false;
    }

    public function getStatusFromRedirection(Request $request): PaymentStatus
    {
        return match ($request->status) {
            'success' => PaymentStatus::SUCCESS,
            'successful' => PaymentStatus::SUCCESS,
            'canceled' => PaymentStatus::CANCELLED,
            'cancelled' => PaymentStatus::CANCELLED,
            'cancel' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };
    }

    protected function getAuthToken()
    {
        return Cache::remember('upay_auth_token', now()->addMinutes(10), function () {
            $response = $this->httpClient()
                ->post('/payment/merchant-auth/', [
                    'merchant_id' => $this->config['merchant_id'],
                    'merchant_key' => $this->config['merchant_key'],
                ]);
            if ($response->successful() && $data = $response->json('data')) {
                return $data['token'];
            }
            throw new Exception($response->json('message', 'Something went wrong.') . ' Unable to get auth token from upay.');
        });
    }

    protected function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->contentType('application/json')
            ->acceptJson()
            ->retry(times: 3, throw: false);
    }
}
