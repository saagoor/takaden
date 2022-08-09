<?php

namespace Takaden\Actions;

use Takaden\Enums\PaymentStatus;
use Takaden\Models\Payment;
use Takaden\Models\Purchase;
use Takaden\Helpers\Currency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Takaden\Models\Coupon;

class GeneratePurchase
{
    public static function fromRequest(Request $request): Purchase
    {
        try {
            DB::beginTransaction();
            $purchase = Purchase::create([
                'customer_id'       => $request->customer_id,
                'purchasable_id'    => $request->purchasable_id,
                'purchasable_type'  => $request->purchasable_type,
                'is_active'         => false,
            ]);

            $isRental = ($request->duration > 0 && $request->is_rental);
            $request->merge(['country' => null]);
            $currency = Currency::current();
            $price =  $isRental ? ($purchase->purchasable->rental_price[$currency] ?? 0) : ($purchase->purchasable->lifetime_price[$currency] ?? 0);

            if ($request->coupon && $coupon = Coupon::firstWhere('code', $request->coupon)) {
                $coupon
                    ->applied()
                    ->attach($purchase->id, [
                        'customer_id' => $purchase->customer_id
                    ]);
                $discount = $coupon->calculateDiscountAmount($price);
            } else {
                $discount = 0;
            }

            $purchase->update([
                'created_at'    => now(),
                'expires_at'    => $purchase->getPurchasableExpireDate($isRental),
                'is_rental'     => $isRental,
                'is_recurring'  => $request->is_recurring ?? false,
            ]);

            $payment = Payment::create([
                'customer_id'   => $purchase->customer_id,
                'purchase_id'   => $purchase->id,
                'status'        => PaymentStatus::INITIATED,
                'created_at'    => now(),
                'amount'        => $price,
                'discount'      => $discount,
                'currency'      => $currency,
            ]);

            $purchase->setRelation('payment', $payment);

            DB::commit();
            return $purchase;
        } catch (Exception $e) {
            report($e);
            DB::rollback();
        }
    }
}
