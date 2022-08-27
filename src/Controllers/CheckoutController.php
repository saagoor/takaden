<?php

namespace Takaden\Controllers;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Takaden\Enums\PaymentStatus;
use Takaden\Orderable;
use Takaden\Payment\PaymentHandler;

class CheckoutController extends Controller
{
    public function initiate(FormRequest|Request $request, string $paymentProvider)
    {
        $request->validate([
            'orderable_id'      => 'required',
            'orderable_type'    => 'required',
        ]);
        $order = $request->orderable_type::findOrFail($request->orderable_id);
        return PaymentHandler::create($paymentProvider)->initiatePayment($order);
    }

    public function execute(FormRequest|Request $request, string $paymentProvider)
    {
        $isSuccessful = PaymentHandler::create($paymentProvider)->executePayment($request);
        if ($isSuccessful) {
            return response()->json(['status' => PaymentStatus::SUCCESS]);
        }
        return abort(400, 'Failed to execute payment.');
    }

    public function redirection(FormRequest|Request $request, string $paymentProvider)
    {
        $status = PaymentHandler::create($paymentProvider)->getStatusFromRedirection($request);
        return match ($status) {
            PaymentStatus::SUCCESS      => $this->success($request, $paymentProvider),
            PaymentStatus::FAILED       => $this->failure($request, $paymentProvider),
            PaymentStatus::CANCELLED    => $this->cancel($request, $paymentProvider),
            default                     => ['message' => 'Invalid payment status.'],
        };
    }

    public function success(FormRequest|Request $request, string $paymentProvider)
    {
        $handler = PaymentHandler::create($paymentProvider);
        try {
            $isSuccessful = $handler->validateSuccessfulPayment($request);
            if ($isSuccessful) {
                $orderable = $handler->afterPaymentSuccessful($request);
                return $this->redirectTo(config('takaden.redirects.success'), $orderable);
            }
            $orderable = $handler->afterPaymentFailed($request);
            return $this->redirectTo(config('takaden.redirects.failure'), $orderable);
        } catch (Exception $e) {
            report($e->getMessage());
        }
        return $this->redirectTo(config('takaden.redirects.failure'));
    }

    public function failure(FormRequest|Request $request, string $paymentProvider)
    {
        try {
            $orderable = PaymentHandler::create($paymentProvider)->afterPaymentFailed($request);
            return $this->redirectTo(config('takaden.redirects.failure'), $orderable);
        } catch (Exception $e) {
            report($e->getMessage());
        }
        return $this->redirectTo(config('takaden.redirects.failure'));
    }

    public function cancel(FormRequest|Request $request, string $paymentProvider)
    {
        try {
            $orderable = PaymentHandler::create($paymentProvider)->afterPaymentCancelled($request);
            return $this->redirectTo(config('takaden.redirects.cancel'), $orderable);
        } catch (Exception $e) {
            report($e->getMessage());
        }
        return $this->redirectTo(config('takaden.redirects.cancel'));
    }

    public function webhook(FormRequest|Request $request, string $paymentProvider)
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

    protected function redirectTo(string $url, ?Orderable $orderable = null)
    {
        if (!$orderable) {
            return redirect()->to(url($url));
        }
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'orderable_id=' . $orderable->id . '&orderable_type=' . $orderable::class;
        return redirect()->to(url($url));
    }
}
