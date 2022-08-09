<?php

namespace Takaden\Payment\Handlers;

use Takaden\Enums\PaymentProviders;
use Takaden\Orderable;
use Takaden\Payment\PaymentHandler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UpayPaymentHandler extends PaymentHandler
{
    public PaymentProviders $gatewayName = PaymentProviders::UPAY;

    protected array $config;

    public function __construct()
    {
        $this->config = [
            'base_url'          => config('takaden.upay.base_url'),
            'merchant_id'       => config('takaden.upay.merchant_id'),
            'merchant_key'      => config('takaden.upay.merchant_key'),
            'merchant_code'     => config('takaden.upay.merchant_code'),
            'merchant_name'     => config('takaden.upay.merchant_name'),
            'merchant_mobile'   => config('takaden.upay.merchant_mobile'),
            'merchant_country'  => config('takaden.upay.merchant_country'),
            'merchant_city'     => config('takaden.upay.merchant_city'),
        ];
        if (!$this->config['base_url'] || !$this->config['merchant_id'] || !$this->config['merchant_key'] || !$this->config['merchant_code'] || !$this->config['merchant_name']) {
            throw new Exception('Upay credentials not found, make sure to add upay base url, merchant id, merchant key, merchant code & merchant name on the .env file.');
        }
    }

    public function initiatePayment(Orderable $order)
    {
        $response = Http::baseUrl($this->config['base_url'])
            ->contentType('application/json')
            ->acceptJson()
            ->withToken($this->getAuthToken(), 'UPAY')
            ->post('/payment/merchant-payment-init/', [
                'date'                      => date('Y-m-d'),
                'txn_id'                    => $order->getTakadenUniqueId(),
                'invoice_id'                => $order->getTakadenUniqueId(),
                'amount'                    => $order->getTakadenAmount(),
                'merchant_id'               => $this->config['merchant_id'],
                'merchant_name'             => $this->config['merchant_name'],
                'merchant_code'             => $this->config['merchant_code'],
                'merchant_country_code'     => $this->config['merchant_country'],
                'merchant_city'             => $this->config['merchant_city'],
                'merchant_category_code'    => $this->config['merchant_code'],
                'merchant_mobile'           => $this->config['merchant_mobile'],
                'transaction_currency_code' => $order->getTakadenCurrency(),
                'redirect_url'              => $order->getTakadenRedirectUrl(),
            ]);
        if ($response->successful() && $data = $response->json('data')) {
            return $data['gateway_url'];
        }
        throw new Exception($response->json('message', 'Something went wrong') . '. Unable to initiate payment with upay.');
    }

    public function validateSuccessfulPayment(Request $request): bool
    {
        $response = Http::baseUrl($this->config['base_url'])
            ->contentType('application/json')
            ->acceptJson()
            ->withToken($this->getAuthToken(), 'UPAY')
            ->get('/payment/single-payment-status/' . $request->txn_id);
        if ($response->successful() && $data = $response->json('data')) {
            return $data['status'] === 'success';
        }
        return false;
    }

    protected function getAuthToken()
    {
        return Cache::remember('upay_auth_token', now()->addMinutes(10), function () {
            $response = Http::baseUrl($this->config['base_url'])
                ->contentType('application/json')
                ->acceptJson()
                ->post('/payment/merchant-auth/', [
                    'merchant_id'   => $this->config['merchant_id'],
                    'merchant_key'  => $this->config['merchant_key'],
                ]);
            if ($response->successful() && $data = $response->json('data')) {
                return $data['token'];
            }
            throw new Exception($response->json('message', 'Something went wrong.') . ' Unable to get auth token from upay.');
        });
    }
}
