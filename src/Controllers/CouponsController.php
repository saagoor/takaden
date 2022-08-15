<?php

namespace Takaden\Controllers;

use Takaden\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Takaden\Enums\Purchasable;
use Takaden\Helpers\Currency;
use Takaden\Rules\Coupon as RulesCoupon;

class CouponsController extends Controller
{
    public function apply(Request $request)
    {
        $request->merge([
            'purchasable_type' => Str::start($request->purchasable_type, 'App\\Models\\'),
        ]);
        $request->validate([
            'coupon' => ['required', new RulesCoupon],
            'purchasable_id' => ['required', Rule::exists($request->purchasable_type, 'id')],
            'purchasable_type' => ['required', Rule::in(Purchasable::values())],
            'customer_id' => 'required|exists:customers,id',
            'is_rental' => 'nullable|boolean',
        ]);

        $coupon = Coupon::firstWhere('code', $request->coupon);
        $purchasable = $request->purchasable_type::find($request->purchasable_id);
        $currency = Currency::current();
        $discount = $coupon->calculateDiscountAmount($request->is_rental ? ($purchasable->rental_price[$currency] ?? 0) : ($purchasable->lifetime_price[$currency] ?? 0));

        return response()->json([
            'message' => 'Coupon applied successfully.',
            'result' => [
                'discount' => $discount,
                'currency' => $currency,
            ],
        ]);
    }
}
