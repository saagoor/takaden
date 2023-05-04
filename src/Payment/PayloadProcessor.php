<?php

namespace Takaden\Payment;

use Carbon\Carbon;
use Takaden\Enums\PaymentProviders;

class PayloadProcessor
{
    public static function process($payload, $provider): array
    {
        return match ($provider) {
            PaymentProviders::SSLCOMMERZ => static::sslCommerz($payload),
            PaymentProviders::PADDLE => static::paddle($payload),
            PaymentProviders::BKASH => static::bkash($payload),
            PaymentProviders::NAGAD => static::nagad($payload),
            PaymentProviders::UPAY => static::upay($payload),
            default => $payload,
        };
    }

    public static function paddle($payload): array
    {
        $billable = json_decode($payload['passthrough'], true);

        return [
            'takaden_id' => $billable['billable_id'], // Payment ID
            'payment_method' => $payload['payment_method'] ?? 'PADDLE',
            'amount' => $payload['sale_gross'],
            'provider' => PaymentProviders::PADDLE,
            'timestamp' => (isset($payload['event_time']) && $payload['event_time']) ? Carbon::parse($payload['event_time']) : now(),
            'providers_transaction_id' => $payload['order_id'],
            'providers_payload' => json_encode($payload),
        ];
    }

    public static function sslCommerz($payload): array
    {
        return [
            'takaden_id' => ($payload['value_a'] ?? null), // Purchase ID
            'payment_method' => $payload['card_issuer'] ?? 'SSL',
            'amount' => $payload['currency_amount'] ?? 0,
            'provider' => PaymentProviders::SSLCOMMERZ,
            'timestamp' => (isset($payload['tran_date']) && $payload['tran_date']) ? Carbon::parse($payload['tran_date']) : now(),
            'providers_payment_id' => $payload['tran_id'] ?? '',
            'providers_transaction_id' => $payload['bank_tran_id'] ?? '',
            'providers_payload' => json_encode($payload),
        ];
    }

    public static function bkash($payload): array
    {
        return [
            'takaden_id' => $payload['merchantInvoiceNumber'] ?? '',
            'payment_method' => 'bkash',
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'provider' => PaymentProviders::BKASH,
            'timestamp' => now(),
            'providers_payment_id' => $payload['paymentID'] ?? $payload['payment_id'] ?? '',
            'providers_transaction_id' => $payload['trxID'] ?? '',
            'providers_payload' => json_encode($payload),
        ];
    }

    public static function nagad($payload): array
    {
        return [
            'takaden_id' => $payload['order_id'],
            'payment_method' => 'nagad',
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'provider' => PaymentProviders::NAGAD,
            'timestamp' => now(),
            'providers_payment_id' => $payload['payment_ref_id'] ?? '',
            'providers_transaction_id' => $payload['payment_ref_id'] ?? '',
            'providers_payload' => json_encode($payload),
        ];
    }

    public static function upay($payload): array
    {
        return [
            'takaden_id' => $payload['invoice_id'],
            'payment_method' => 'upay',
            'amount' => null,
            'currency' => null,
            'provider' => PaymentProviders::UPAY,
            'timestamp' => now(),
            'providers_payment_id' => $payload['trx_id'] ?? null,
            'providers_transaction_id' => $payload['trx_id'] ?? null,
            'providers_payload' => json_encode($payload),
        ];
    }
}
