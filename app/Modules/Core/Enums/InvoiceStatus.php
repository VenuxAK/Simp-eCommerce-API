<?php

namespace App\Modules\Core\Enums;

/**
 * Financial lifecycle of an invoice — tracks payment state separately from order fulfillment.
 *
 * Issued after order confirmation, transitions to Paid on payment capture.
 * Refunded reverses a paid invoice; Cancelled voids an unpaid one.
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
