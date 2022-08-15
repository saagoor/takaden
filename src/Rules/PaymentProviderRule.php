<?php

namespace Takaden\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Takaden\Enums\PaymentProviders;

class PaymentProviderRule implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (in_array($value, PaymentProviders::values())) {
            $fail('The :attribute is invalid.');
        }
    }
}
