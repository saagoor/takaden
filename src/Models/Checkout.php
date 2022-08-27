<?php

namespace Takaden\Models;

use Illuminate\Database\Eloquent\Model;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;

class Checkout extends Model
{
    protected $guarded = [];

    protected $table = 'takaden_checkouts';

    public $casts = [
        'payment_provider' => PaymentProviders::class,
        'payment_status' => PaymentStatus::class,
        'payload' => 'array',
    ];

    public function orderable()
    {
        return $this->morphTo();
    }
}
