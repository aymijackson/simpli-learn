<?php

namespace Elibrary\Library\Payments;

use Elibrary\Library\Enums\LibraryPaymentGateway;

class PaymentGatewayFactory
{
    private const MAP = [
        'stripe' => StripeGateway::class,
        'paystack' => PaystackGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
    ];

    public function make(LibraryPaymentGateway $gateway): ?PaymentGatewayContract
    {
        $class = self::MAP[$gateway->value] ?? null;

        return $class ? app($class) : null;
    }
}
