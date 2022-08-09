<?php

namespace Takaden\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Takaden\Actions\GeneratePurchase;
use Takaden\Helpers\Currency;
use Takaden\Models\Purchase;
use Takaden\Payment\Handlers\SSLCommerzPaymentHandler;
use Takaden\Payment\PaymentHandler;
use Takaden\Requests\PaymentRequest;

class PaymentController extends Controller
{
    protected PaymentHandler $handler;

    protected string $redirectBaseUrl;

    public function __construct()
    {
        $this->handler = new SSLCommerzPaymentHandler;
        $this->redirectBaseUrl = config('app.frontend_url').'/payment';
    }

    public function validatePurchase(PaymentRequest $request)
    {
        $isRental = ($request->duration > 0 && $request->is_rental);
        $request->merge(['country' => null]); // Ignore user passed country, to get actual currency manually.
        $currency = Currency::current();
        $purchase = new Purchase($request->validated());
        $price = $isRental ? ($purchase->purchasable->rental_price[$currency] ?? 0) : ($purchase->purchasable->lifetime_price[$currency] ?? 0);

        return response()->json([
            'message' => 'Validation successful.',
            'result' => [
                'data' => [
                    ...$request->validated(),
                    'is_rental' => $isRental,
                    'price' => $price,
                ],
            ],
        ]);
    }

    public function create(PaymentRequest $request)
    {
        $this->handler->beforePaymentCreate($request);
        $purchase = GeneratePurchase::fromRequest($request);

        return $this->handler->initiatePayment($purchase);
    }

    public function success(Request $request)
    {
        try {
            $isSuccessful = $this->handler->validateSuccessfulPayment($request);
            if ($isSuccessful) {
                $this->handler->afterPaymentSuccessful($request);

                return redirect()->to(url($this->redirectBaseUrl.'/success'));
            }
            $this->handler->afterPaymentFailed($request);
        } catch (Exception $e) {
            logger($e->getMessage());
        }

        return redirect()->to($this->redirectBaseUrl.'/failure');
    }

    public function failure(Request $request)
    {
        try {
            $this->handler->afterPaymentFailed($request);
        } catch (Exception $e) {
            logger($e->getMessage());
        }

        return redirect()->to($this->redirectBaseUrl.'/failure');
    }

    public function cancel(Request $request)
    {
        try {
            $this->handler->afterPaymentCancelled($request);
        } catch (Exception $e) {
            logger($e->getMessage());
        }

        return redirect()->to($this->redirectBaseUrl.'/failure');
    }

    public function webhook(Request $request)
    {
        $isSuccessful = $this->handler->validateSuccessfulPayment($request);
        if ($isSuccessful) {
            $this->handler->afterPaymentSuccessful($request);
        } else {
            $this->handler->afterPaymentFailed($request);
        }

        return response()->json([
            'success' => $isSuccessful,
            'payload' => $request->all(),
        ]);
    }
}
