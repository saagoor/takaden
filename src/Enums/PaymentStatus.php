<?php

namespace Takaden\Enums;

enum PaymentStatus: string
{
    case INITIATED = 'initiated';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case SUCCESS = 'success';
    case REFUNDED = 'refunded';

    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }
}
