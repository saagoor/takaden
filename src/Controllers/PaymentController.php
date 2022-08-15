<?php

namespace Takaden\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Takaden\Payment\PaymentHandler;

class PaymentController extends Controller
{
    public function success(Request $request, string $paymentProvider)
    {
        $handler = PaymentHandler::create($paymentProvider);
        try {
            $isSuccessful = $handler->validateSuccessfulPayment($request);
            if ($isSuccessful) {
                $handler->afterPaymentSuccessful($request);
                return redirect()->to(config('takaden.redirects.success'));
            }
            $handler->afterPaymentFailed($request);
        } catch (Exception $e) {
            logger($e->getMessage());
        }
        return redirect()->to(config('takaden.redirects.failure'));
    }

    public function failure(Request $request, string $paymentProvider)
    {
        try {
            PaymentHandler::create($paymentProvider)->afterPaymentFailed($request);
        } catch (Exception $e) {
            logger($e->getMessage());
        }
        return redirect()->to(config('takaden.redirects.failure'));
    }

    public function cancel(Request $request, string $paymentProvider)
    {
        try {
            PaymentHandler::create($paymentProvider)->afterPaymentCancelled($request);
        } catch (Exception $e) {
            logger($e->getMessage());
        }
        return redirect()->to(config('takaden.redirects.failure'));
    }

    public function webhook(Request $request, string $paymentProvider)
    {
        $handler = PaymentHandler::create($paymentProvider);
        $isSuccessful = $handler->validateSuccessfulPayment($request);
        if ($isSuccessful) {
            $handler->afterPaymentSuccessful($request);
        } else {
            $handler->afterPaymentFailed($request);
        }
        return response()->json([
            'success' => $isSuccessful,
            'payload' => $request->all(),
        ]);
    }
}
