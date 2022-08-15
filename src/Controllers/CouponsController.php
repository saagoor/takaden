<?php

namespace Takaden\Controllers;

use Takaden\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Takaden\Helpers\Currency;
use Takaden\Rules\Coupon as RulesCoupon;

class CouponsController extends Controller
{
    public function apply(Request $request)
    {
        $request->merge([
            'orderable_type' => Str::start($request->orderable_type, 'App\\Models\\'),
        ]);
        $request->validate([
            'coupon'            => ['required', new RulesCoupon],
            'orderable_id'      => ['required', Rule::exists($request->orderable_type, 'id')],
            'orderable_type'    => ['required'],
            'customer_id'       => 'required|exists:customers,id',
        ]);
        $coupon = Coupon::firstWhere('code', $request->coupon);
        $orderable = $request->orderable_type::find($request->orderable_id);
        $currency = Currency::current();
        $discount = $coupon->calculateDiscountAmount($orderable->getTakadenAmount());
        return response()->json([
            'message' => 'Coupon applied successfully.',
            'result' => [
                'discount' => $discount,
                'currency' => $currency,
            ],
        ]);
    }
}
