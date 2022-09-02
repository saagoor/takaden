<?php

namespace Takaden\Payment\Handlers;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;
use Takaden\Helpers\Currency;
use Takaden\Orderable;
use Takaden\Payment\PaymentHandler;

class NagadPaymentHandler extends PaymentHandler
{
    public PaymentProviders $providerName = PaymentProviders::NAGAD;

    public array $config;

    public function __construct()
    {
        $this->config = [
            'base_url' => config('takaden.providers.nagad.base_url'),
            'merchant_id' => config('takaden.providers.nagad.merchant_id'),
            'merchant_phone' => config('takaden.providers.nagad.merchant_phone'),
            'public_key' => config('takaden.providers.nagad.public_key'),
            'private_key' => config('takaden.providers.nagad.private_key'),
        ];
        if (!$this->config['base_url'] || !$this->config['merchant_id'] || !$this->config['merchant_phone'] || !$this->config['public_key'] || !$this->config['private_key']) {
            throw new Exception('Nagad credentials are missing, make sure to add nagad credentials on the .env file.');
        }
        if (!Str::startsWith($this->config['public_key'], "-----BEGIN PUBLIC KEY-----" . PHP_EOL)) {
            $this->config['public_key'] = "-----BEGIN PUBLIC KEY-----" . PHP_EOL . $this->config['public_key'];
        }
        if (!Str::endsWith($this->config['public_key'], PHP_EOL . "\n-----END PUBLIC KEY-----")) {
            $this->config['public_key'] .= PHP_EOL . "-----END PUBLIC KEY-----";
        }
        if (!Str::startsWith($this->config['private_key'], "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL)) {
            $this->config['private_key'] = "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL . $this->config['private_key'];
        }
        if (!Str::endsWith($this->config['private_key'], PHP_EOL . "-----END RSA PRIVATE KEY-----")) {
            $this->config['private_key'] .= PHP_EOL . "-----END RSA PRIVATE KEY-----";
        }
    }

    public function initiatePayment(Orderable $order)
    {
        $checkout = $this->createCheckout($order);
        $orderId = strlen($checkout->id) < 5 ? sprintf('%05d', $checkout->id) : (string) $checkout->id;
        $initialSensitiveData = [
            'merchantId' => $this->config['merchant_id'],
            'datetime' => now('Asia/Dhaka')->format('YmdHis'),
            'orderId' => $orderId,
            'challenge' => Str::random(40),
        ];
        $response = $this->httpClient()->post('/check-out/initialize/' . $this->config['merchant_id'] . '/' . $orderId . '?locale=EN', [
            'accountNumber' => $this->config['merchant_phone'],
            'dateTime' => $initialSensitiveData['datetime'],
            'sensitiveData' => $this->encryptWithPublicKey(json_encode($initialSensitiveData)),
            'signature' => $this->signWithPrivateKey(json_encode($initialSensitiveData)),
        ]);
        $data = $response->json();
        logger($data);
        if ($response->successful() && isset($data['sensitiveData']) && isset($data['signature']) && $data['sensitiveData'] && $data['signature']) {
            $decryptedData = json_decode($this->decryptWithPrivateKey($data['sensitiveData']), true);
            logger($decryptedData);
            if (!isset($decryptedData['paymentReferenceId']) || !isset($decryptedData['challenge'])) {
                return throw new Exception('Invalid response from Nagad.');
            }
            $sensitiveData = [
                'merchantId' => $this->config['merchant_id'],
                'orderId' => $orderId,
                'amount' => $order->getTakadenAmount(),
                'currencyCode' => sprintf('%03d', Currency::numericCode($order->getTakadenCurrency())),
                'challenge' => $decryptedData['challenge'],
            ];
            $response = $this->httpClient()->post('/check-out/complete/' . $decryptedData['paymentReferenceId'], [
                'sensitiveData' => $this->encryptWithPublicKey(json_encode($sensitiveData)),
                'signature' => $this->signWithPrivateKey(json_encode($sensitiveData)),
                'merchantCallbackURL' => route('takaden.checkout.redirection', $this->providerName),
            ]);
            $data = $response->json();
            logger($data);
            if ($response->successful() && $data && isset($data['status']) && $data['status'] == 'Success') {
                $this->afterInitiatePayment($checkout, $decryptedData['paymentReferenceId'], array_merge($data, $decryptedData));

                return $data['callBackUrl'];
            }
        }
        if ($response->serverError()) {
            return throw new Exception($data['message']);
        }
        if ($response->failed()) {
            return throw new Exception('Failed to initiate payment.');
        }

        return throw new Exception('Something went wrong, unknown error.');
    }

    public function validateSuccessfulPayment(Request $request): bool
    {
        $response = $this->httpClient()->get('/verify/payment/' . $request->payment_ref_id);
        $data = $response->json();
        logger($data);
        if ($response->successful() && $data && isset($data['status']) && $data['status'] == 'Success') {
            return true;
        }

        return false;
    }

    public function getStatusFromRedirection(Request $request): PaymentStatus
    {
        return match ($request->status) {
            'Success' => PaymentStatus::SUCCESS,
            'Failed' => PaymentStatus::FAILED,
            'Aborted' => PaymentStatus::CANCELLED,
            'Cancelled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };
    }

    protected function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->withHeaders([
                'X-KM-IP-V4' => request()->ip(),
                'X-KM-Client-Type' => 'PC_WEB',
                'X-KM-Api-Version' => 'v-0.2.0',
                'Content-Type' => 'application/json',
            ])
            ->retry(3, 0, fn ($exception, $request) => $exception instanceof ConnectionException, false);
    }

    protected function encryptWithPublicKey(string $data)
    {
        $key = openssl_get_publickey($this->config['public_key']);
        openssl_public_encrypt($data, $encrypted, $key);

        return base64_encode($encrypted);
    }

    protected function decryptWithPrivateKey(string $data)
    {
        $key = openssl_get_privatekey($this->config['private_key']);
        openssl_private_decrypt(base64_decode($data), $decrypted, $key);

        return $decrypted;
    }

    protected function signWithPrivateKey(string $data)
    {
        $key = $this->config['private_key'];
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }
}
