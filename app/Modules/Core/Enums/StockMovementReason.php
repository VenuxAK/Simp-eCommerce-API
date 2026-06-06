<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible StockMovement reason values.
 */
enum StockMovementReason: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Return = 'return';
    case Refunded = 'refunded';
}
