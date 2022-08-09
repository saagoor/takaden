<?php

namespace Takaden\Payment;

use Takaden\Enums\PaymentProviders;
use Carbon\Carbon;

class PayloadProcessor
{
    public static function process($payload, $provider)
    {
        return match ($provider) {
            PaymentProviders::SSLCOMMERZ    => static::sslCommerz($payload),
            PaymentProviders::PADDLE        => static::paddle($payload),
            PaymentProviders::BKASH         => static::bkash($payload),
            PaymentProviders::UPAY          => static::upay($payload),
            default                         => $payload,
        };
    }

    public static function paddle($payload)
    {
        $billable = json_decode($payload['passthrough'], true);
        return [
            'takaden_transaction_id'    => $billable['billable_id'], // Payment ID
            'payment_method'            => $payload['payment_method'] ?? 'PADDLE',
            'amount'                    => $payload['sale_gross'],
            'provider'                  => PaymentProviders::PADDLE,
            'paid_at'                   => (isset($payload['event_time']) && $payload['event_time']) ? Carbon::parse($payload['event_time']) : now(),
            'providers_transaction_id'  => $payload['order_id'],
            'providers_payload'         => json_encode($payload),
        ];
    }

    public static function sslCommerz($payload)
    {
        return [
            'takaden_transaction_id'    => ($payload['value_a'] ?? null), // Purchase ID
            'payment_method'            => $payload['card_issuer'] ?? 'SSL',
            'amount'                    => $payload['currency_amount'] ?? 0,
            'provider'                  => PaymentProviders::SSLCOMMERZ,
            'paid_at'                   => (isset($payload['tran_date']) && $payload['tran_date']) ? Carbon::parse($payload['tran_date']) : now(),
            'providers_payment_id'      => $payload['tran_id'] ?? '',
            'providers_transaction_id'  => $payload['bank_tran_id'] ?? '',
            'providers_payload'         => json_encode($payload),
        ];
    }

    public static function bkash($payload)
    {
        return [
            'takaden_transaction_id'    => ($payload['value_a'] ?? null),
            'payment_method'            => 'bkash',
            'amount'                    => $payload['amount'] ?? 0,
            'currency'                  => $payload['currency'],
            'provider'                  => PaymentProviders::BKASH,
            'paid_at'                   => now(),
            'providers_payment_id'      => $payload['paymentID'] ?? '',
            'providers_transaction_id'  => $payload['trxID'] ?? '',
            'providers_payload'         => json_encode($payload),
        ];
    }

    public static function upay($payload)
    {
        return [
            'takaden_transaction_id'    => $payload['txn_id'],
            'payment_method'            => 'upay',
            'amount'                    => null,
            'currency'                  => null,
            'provider'                  => PaymentProviders::UPAY,
            'paid_at'                   => now(),
            'providers_payment_id'      => $payload['trx_id'] ?? '',
            'providers_transaction_id'  => $payload['trx_id'] ?? '',
            'providers_payload'         => json_encode($payload),
        ];
    }
}
