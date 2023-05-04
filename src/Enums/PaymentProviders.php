<?php

namespace Takaden\Enums;

use Takaden\Payment\Handlers\BkashPaymentHandler;
use Takaden\Payment\Handlers\BkashUrlBasedPaymentHandler;
use Takaden\Payment\Handlers\NagadPaymentHandler;
use Takaden\Payment\Handlers\SSLCommerzPaymentHandler;
use Takaden\Payment\Handlers\UpayPaymentHandler;
use Takaden\Payment\PaymentHandler;

enum PaymentProviders: string
{
    // Aggregators
    case SSLCOMMERZ = 'sslcommerz';
    case AAMARPAY = 'aamarpay';
    case CHECKOUT2 = 'checkout2';
    case PORTWALLET = 'portwallet';
    case SHURJAPAY = 'shurjapay';
    case FASTSPRING = 'fastspring';
    case PADDLE = 'paddle';
    case STRIPE = 'stripe';

    // Gateways
    case BKASH = 'bkash';
    case NAGAD = 'nagad';
    case ROCKET = 'rocket';
    case UPAY = 'upay';

    // Cash
    case CASH = 'cash';
    case BALANCE = 'balance';
    case BANK_TRANSFER = 'bank_transfer';
    case NONE = '';

    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    public function getHandler(): PaymentHandler
    {
        return match ($this) {
            self::BKASH => config('takaden.providers.bkash.checkout_mode') === 'url_based' ? new BkashUrlBasedPaymentHandler : new BkashPaymentHandler,
            self::NAGAD => new NagadPaymentHandler,
            self::UPAY => new UpayPaymentHandler,
            self::SSLCOMMERZ => new SSLCommerzPaymentHandler,
        };
    }
}
