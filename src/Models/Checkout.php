<?php

namespace Takaden\Models;

use Illuminate\Database\Eloquent\Model;
use Takaden\Enums\PaymentProviders;

class Checkout extends Model
{
    protected $guarded = [];

    protected $table = 'takaden_checkouts';

    public $casts = [
        'payment_provider'  => PaymentProviders::class,
        'payload'           => 'array',
    ];

    public function orderable()
    {
        return $this->morphTo();
    }
}
