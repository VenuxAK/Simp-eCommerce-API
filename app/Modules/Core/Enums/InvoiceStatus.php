<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible InvoiceStatus values.
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
