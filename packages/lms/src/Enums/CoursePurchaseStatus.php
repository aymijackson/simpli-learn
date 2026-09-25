<?php

namespace Elibrary\Lms\Enums;

enum CoursePurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
