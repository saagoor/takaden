<?php

namespace Takaden\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\PaymentStatus;

class Payment extends Model
{
    protected $guarded = ['payable_total'];

    protected $casts = [
        'status' => PaymentStatus::class,
        'provider' => PaymentProviders::class,
        'paid_at' => 'datetime',
        'providers_payload' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function title(): Attribute
    {
        return Attribute::make(get: fn () => $this->purchase?->getPaymentTitle().' '.$this->purchase?->purchasable?->title);
    }

    public function payableTotal(): Attribute
    {
        return Attribute::make(get: fn () => $this->amount - $this->discount);
    }
}
