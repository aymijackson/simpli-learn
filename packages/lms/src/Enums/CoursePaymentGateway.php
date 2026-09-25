<?php

namespace Elibrary\Lms\Enums;

enum CoursePaymentGateway: string
{
    case Stripe = 'stripe';
    case Paystack = 'paystack';
    case Flutterwave = 'flutterwave';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::Paystack => 'Paystack',
            self::Flutterwave => 'Flutterwave',
            self::BankTransfer => 'Bank transfer',
        };
    }
}
