<?php

namespace Elibrary\Cbt\Enums;

enum CertificateTier: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Verified => 'Verified',
        };
    }
}
