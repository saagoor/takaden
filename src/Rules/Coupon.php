<?php

namespace Takaden\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;
use Takaden\Helpers\Currency;
use Takaden\Models\Coupon as ModelsCoupon;

class Coupon implements Rule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    protected string $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $errorMessage = null;
        $orderableId = $this->data['orderable_id'];
        $orderableType = $this->data['orderable_type'];
        $customerId = $this->data['customer_id'] ?? auth('api')->id() ?? auth()->id();

        $coupon = ModelsCoupon::query()
            ->active()
            ->where('code', $value)
            ->withCount('applied')
            ->first();

        if (!$coupon) {
            $errorMessage = 'The entered :attribute doen\'t exists.';
        } elseif ($coupon->applicable_type && $coupon->applicable_type->value != $orderableType) {
            $errorMessage = 'This :attribute is not applicable for ' . Str::plural(class_basename($orderableType)) . '.';
        } elseif ($coupon->applicable_id && $coupon->applicable_id != $orderableId) {
            $errorMessage = 'This :attribute is not applicable for this ' . class_basename($orderableType) . '.';
        } elseif ($coupon->starts_at->isAfter(now())) {
            // Coupon hasn't started yet.
            $errorMessage = 'This :attribute is for future :\').';
        } elseif ($coupon->expires_at->isBefore(now())) {
            // Coupon has reached it's expire date
            $errorMessage = 'This :attribute has expired.';
        } elseif (!($coupon->max_uses < 0) && $coupon->applied_count >= $coupon->max_uses) {
            // Not unlimited & applied maximum times
            $errorMessage = 'This :attribute has reached it\'s quota.';
        } elseif ($coupon->max_uses_per_customer === 0) {
            // Customers cannot apply this :attribute (0 value)
            $errorMessage = 'You cannot apply this :attribute.';
        } elseif (
            !($coupon->max_uses_per_customer < 0) &&
            ($count = $coupon->applied()->wherePivot('customer_id', $customerId)->count()) >= $coupon->max_uses_per_customer
        ) {
            // Not unlimited per customer & and applied the times of its limit.
            $errorMessage = 'You already applied this :attribute' . ($count <= 1 ? '.' : ' ' . $count . ' times.');
        } elseif ($coupon->currency && $coupon->currency != Currency::current()) {
            $errorMessage = 'This :attribute is not applicable in your region.';
        }

        if ($errorMessage) {
            $this->message = $errorMessage;

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
