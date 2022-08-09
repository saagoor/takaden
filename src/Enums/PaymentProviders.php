<?php

namespace Takaden\Enums;

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
    case NONE = '';

    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }
}
