<?php

namespace Takaden\Payment\Handlers;

use DGvai\SSLCommerz\SSLCommerz;
use Exception;
use Illuminate\Http\Request;
use Takaden\Enums\PaymentProviders;
use Takaden\Orderable;
use Takaden\Payment\PaymentHandler;

class SSLCommerzPaymentHandler extends PaymentHandler
{
    public PaymentProviders $gatewayName = PaymentProviders::SSLCOMMERZ;

    public function initiatePayment(Orderable $order)
    {
        $customer = $order->getTakadenCustomer();
        $email = $customer->email ?? config('mail.from.address', 'hello@example.com');
        $name = ($customer->name ?? config('app.name').' Customer');
        $phone = $customer->phone;

        $sslc = (new SSLCommerz)
            ->amount($order->getTakadenAmount())
            ->setCurrency($order->getTakadenCurrency())
            ->trxid($order->getTakadenUniqueId())
            ->product($order->getTakadenPaymentTitle())
            ->customer($name, $email, $phone)
            ->setExtras($order->getTakadenUniqueId()); // `value_a` is Payment ID

        return $sslc->make_payment(true);
    }

    public function validateSuccessfulPayment(Request $request): bool
    {
        try {
            return SSLCommerz::validate_payment($request) ?? false;
        } catch (Exception $e) {
            report($e);
        }

        return false;
    }
}
