<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible OrderStatus values.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
