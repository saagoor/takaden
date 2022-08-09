<?php

namespace Takaden\Requests;

use App\Rules\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Takaden\Enums\PaymentProviders;
use Takaden\Enums\Purchasable;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->cart_json) {
            // Merge sslcommerz payload
            $this->merge(json_decode($this->cart_json, true));
        }
        $this->merge([
            'purchasable_type' => Str::start($this->purchasable_type, 'App\\Models\\'),
        ]);
        if (is_array($this->customer_id)) {
            $this->merge(['customer_id' => $this->customer_id[0]]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'provider' => ['required', Rule::in(PaymentProviders::values())],
            'is_recurring' => 'nullable|boolean',
            'is_rental' => 'nullable|boolean',
            'purchasable_id' => ['required', 'integer', Rule::exists($this->purchasable_type, 'id')],
            'purchasable_type' => ['required', 'string', Rule::in(Purchasable::values())],
            'duration' => 'required|integer',
            'coupon' => ['nullable', new Coupon()],
        ];
    }
}
