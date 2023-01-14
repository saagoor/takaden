<?php

namespace Takaden\Payment\Handlers;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;
use Takaden\Models\Checkout;
use Takaden\Orderable;
use Takaden\Payment\PaymentHandler;

class BkashPaymentHandler extends PaymentHandler
{
    public PaymentProviders $providerName = PaymentProviders::BKASH;

    protected array $config;

    public function __construct()
    {
        $this->config = [
            'app_key' => config('takaden.providers.bkash.app_key'),
            'app_secret' => config('takaden.providers.bkash.app_secret'),
            'username' => config('takaden.providers.bkash.username'),
            'password' => config('takaden.providers.bkash.password'),
            'base_url' => config('takaden.providers.bkash.base_url'),
            'script_url' => config('takaden.providers.bkash.script_url'),
            'intent' => config('takaden.providers.bkash.intent'),
        ];

        if (! $this->config['app_key'] || ! $this->config['app_secret']) {
            throw new Exception('Bkash credentials not found, make sure to add bkash app key & app secret on the .env file.');
        }
    }

    public function initiatePayment(Orderable $order)
    {
        $checkout = $this->createCheckout($order);
        $payload = [
            'app_key' => $this->config['app_key'],
            'app_secret' => $this->config['app_secret'],
            'intent' => 'authorization',
            'amount' => $order->getTakadenAmount(),
            'currency' => $order->getTakadenCurrency(),
            'merchantInvoiceNumber' => $checkout->id,
        ];

        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->post('/checkout/payment/create', $payload);
        $data = $response->json();
        $this->afterInitiatePayment($checkout, $data['paymentID'], $data);

        return $data;
    }

    public function executePayment(Request $request): bool
    {
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->post('/checkout/payment/execute/'.$request->payment_id);
        $data = $response->json();
        logger('Execution');
        logger($data);
        if ($response->successful() && $data && isset($data['trxID']) && isset($data['transactionStatus'])) {
            // Capture payment if payment is not completed or authorized
            if (! ($data['transactionStatus'] == 'Completed' || $data['transactionStatus'] == 'Authorized')) {
                $response = $this->httpClient()
                    ->withHeaders(['x-app-key' => $this->config['app_key']])
                    ->withToken($this->getToken())
                    ->post('/checkout/payment/capture/'.$request->payment_id);
                logger('Capture');
                logger($response->json());
                $data = array_merge($data, $response->json());
            }
            if ($response->successful() && $data['transactionStatus'] === 'Completed' || $data['transactionStatus'] == 'Authorized') {
                $this->afterPaymentSuccessful($request->merge($data));

                return true;
            }
        } elseif ($response->serverError() || ($data && isset($data['errorCode']) && (in_array((int) $data['errorCode'], [2029, 2062, 2068])))) {
            // Verify, request fails
            // or the payment has already been completed
            logger('Verify, incase the payment has already been completed');
            if ($this->validateSuccessfulPayment($request)) {
                logger('Payment is successful');
                $this->afterPaymentSuccessful($request->merge($data));

                return true;
            }
            logger('Payment is not successful');
        }
        // Add 'merchantInvoiceNumber' with the payload for payment identification
        if (! isset($data['merchantInvoiceNumber']) || ! $data['merchantInvoiceNumber']) {
            $data['merchantInvoiceNumber'] = Checkout::where('payment_provider', $this->providerName)->where('providers_payment_id', $request->payment_id)->first()?->getKey();
        }
        logger($request->merge($data));
        $this->afterPaymentFailed($request->merge($data));
        // Early abort with bkash gateway error message
        if (isset($data['errorMessage']) && $data['errorMessage']) {
            abort(400, 'bKash gateway error: '.$data['errorMessage']);
        }

        return false;
    }

    public function refundPayment(Checkout $checkout): bool
    {
        if (! isset($checkout->payload['providers_transaction_id']) || ! $checkout->payload['providers_transaction_id']) {
            throw new Exception('Unable to refund, transaction ID not found.');
        }
        $payload = [
            'paymentID' => $checkout->providers_payment_id,
            'amount' => $checkout->amount,
            'trxID' => $checkout->payload['providers_transaction_id'],
            'sku' => $checkout->id,
            'reason' => 'Refund',
        ];
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->post('/checkout/payment/refund', $payload);
        $data = $response->json();
        logger('Refund');
        logger($data);
        if ($response->successful() && $data && array_key_exists('transactionStatus', $data) && ($data['transactionStatus'] === 'Completed')) {
            $this->afterPaymentRefunded(request()->merge($payload)->merge($data));

            return true;
        }

        return false;
    }

    public function getRefundStatus(Checkout $checkout): bool
    {
        if (! isset($checkout->payload['providers_transaction_id']) || ! $checkout->payload['providers_transaction_id']) {
            throw new Exception('Unable to refund, transaction ID not found.');
        }
        $payload = [
            'paymentID' => $checkout->providers_payment_id,
            'trxID' => $checkout->payload['providers_transaction_id'],
        ];
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->post('/checkout/payment/refund', $payload);
        $data = $response->json();

        logger($data);

        return false;
    }

    public function validateSuccessfulPayment(Request $request): bool
    {
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->get('/checkout/payment/query/'.$request->payment_id);
        logger('Query');
        logger($response->json());
        logger($request->all());

        return false;
    }

    public function getPaymentStatus(Checkout $checkout): PaymentStatus
    {
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->get('/checkout/payment/query/'.$checkout->providers_payment_id);
        $data = $response->json();
        logger('Query');
        logger($data);
        if (! $response->successful() || ! $data || ! array_key_exists('transactionStatus', $data)) {
            throw new Exception('Unable to get payment status. '.($data['statusMessage'] ?? ''));
        }

        return match ($data['transactionStatus']) {
            'Initiated' => PaymentStatus::INITIATED,
            'Pending' => PaymentStatus::PENDING,
            'Pending Authorized' => PaymentStatus::PENDING,
            'Completed' => PaymentStatus::SUCCESS,
            'Authorized' => PaymentStatus::SUCCESS,
            'Canceled' => PaymentStatus::CANCELLED,
            'Cancelled' => PaymentStatus::CANCELLED,
            'Refunded' => PaymentStatus::REFUNDED,
            'Expired' => PaymentStatus::FAILED,
            'Failed' => PaymentStatus::FAILED,
            'Declined' => PaymentStatus::FAILED,
            default => PaymentStatus::FAILED,
        };
    }

    public function getPaymentInfo(Checkout $checkout): array
    {
        if (! isset($checkout->payload['providers_transaction_id']) || ! $checkout->payload['providers_transaction_id']) {
            throw new Exception('Unable to refund, transaction ID not found.');
        }
        $response = $this->httpClient()
            ->withHeaders(['x-app-key' => $this->config['app_key']])
            ->withToken($this->getToken())
            ->get('/checkout/payment/search/'.$checkout->payload['providers_transaction_id']);
        $data = $response->json();
        logger('Search');
        logger($data);

        return $data;
    }

    protected function getToken(): string
    {
        $token = Cache::get('takaden.bkash.token');
        if ($token && ! $this->isTokenExpiringSoon($token)) {
            return $token['id_token'];
        }

        $payload = [
            'app_key' => $this->config['app_key'],
            'app_secret' => $this->config['app_secret'],
        ];
        $endpoint = '/checkout/token/grant';

        // Refresh token if already has a token & it's expiring but not yet expired.
        if ($token && $this->isTokenExpiringSoon($token) && ! $this->isTokenExpired($token)) {
            $payload['refresh_token'] = $token['refresh_token'];
            $endpoint = '/checkout/token/refresh';
        }

        $response = $this
            ->httpClient()
            ->withHeaders([
                'username' => $this->config['username'],
                'password' => $this->config['password'],
            ])
            ->post($endpoint, $payload);

        if ($response->failed() || $response->json('status') === 'fail') {
            throw new Exception($response->json('msg', 'Something went wrong').', could not get bkash access token.');
        }

        $token = $response->json();
        $token['created_at'] = time();
        Cache::put('takaden.bkash.token', $token, $token['expires_in']);

        return $token['id_token'];
    }

    protected function httpClient(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->contentType('application/json')
            ->acceptJson()
            ->retry(3, 0, fn ($exception, $request) => $exception instanceof ConnectionException, false);
    }

    protected function isTokenExpiringSoon(array $token): bool
    {
        return ((time() - $token['created_at']) < ($token['expires_in'] - 60 * 10)) ? false : true;
    }

    protected function isTokenExpired(array $token): bool
    {
        return ((time() - $token['created_at']) < $token['expires_in']) ? false : true;
    }
}
