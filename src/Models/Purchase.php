<?php

namespace Takaden\Models;

use App\Models\Customer;
use App\Models\Package;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Support\Carbon;
use Takaden\Enums\Purchasable;

class Purchase extends Model
{
    use AsPivot;

    protected $table = 'purchases';

    protected $guarded = [];

    protected $casts = [
        'purchasable_type' => Purchasable::class,
        'is_active' => 'boolean',
        'is_trial' => 'boolean',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getPaymentTitle()
    {
        return class_basename($this->purchasable_type->value).' #'.$this->purchasable_id;
    }

    public function getPurchasableExpireDate(bool $isRental)
    {
        if ($this->purchasable_type->value == Package::class) {
            return Carbon::now()->addDays($this->purchasable->duration);
        }
        if ($isRental) {
            return Carbon::now()->addDays($this->purchasable->rental_duration);
        }

        return  null;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchasable()
    {
        return $this->morphTo('purchasable');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'purchase_id')->latestOfMany('updated_at');
    }
}
