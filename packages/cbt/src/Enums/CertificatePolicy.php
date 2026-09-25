<?php

namespace Elibrary\Cbt\Enums;

enum CertificatePolicy: string
{
    case Inherit = 'inherit';
    case None = 'none';
    case Free = 'free';
    case Freemium = 'freemium';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Inherit => "Use the organization's default",
            self::None => 'No certificate for this exam',
            self::Free => 'Free — issued automatically on passing',
            self::Freemium => 'Freemium — free certificate, paid verified upgrade',
            self::Paid => 'Paid — no certificate until payment',
        };
    }
}
