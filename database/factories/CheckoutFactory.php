<?php

namespace Takaden\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Takaden\Enums\PaymentProviders;
use Takaden\Models\Checkout;

class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    public function definition()
    {
        return [
            'payment_provider' => $this->faker->randomElement(PaymentProviders::cases()),
            'orderable_id' => $this->faker->numberBetween(1, 100),
            'orderable_type' => 'Takaden\Models\Checkout',
            'amount' => 0,
        ];
    }
}
