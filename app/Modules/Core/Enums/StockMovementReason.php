<?php

namespace App\Modules\Core\Enums;

/**
 * Why inventory changed — drives accounting entries and audit trails.
 *
 * Sale reduces stock; Purchase increases it; Adjustment is for manual corrections;
 * Return and Refunded restore stock after cancellations.
 */
enum StockMovementReason: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Return = 'return';
    case Refunded = 'refunded';
}
