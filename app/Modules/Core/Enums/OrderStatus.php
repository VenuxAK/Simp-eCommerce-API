<?php

namespace App\Modules\Core\Enums;

/**
 * Lifecycle states for an order through fulfillment.
 *
 * Flow: Pending → Processing → Shipped → Delivered → Completed.
 * Cancelled and Refunded are terminal states reachable from most active states.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
