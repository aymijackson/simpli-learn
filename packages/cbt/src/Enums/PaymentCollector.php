<?php

namespace Elibrary\Cbt\Enums;

enum PaymentCollector: string
{
    case Tenant = 'tenant';
    case Platform = 'platform';
}
