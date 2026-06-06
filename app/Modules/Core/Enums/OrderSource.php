<?php

namespace App\Modules\Core\Enums;

/**
 * Origin of an order — distinguishes in-person POS sales from online storefront orders.
 *
 * Used for financial reporting, fulfillment routing, and commission calculations.
 */
enum OrderSource: string
{
    case Pos = 'pos';
    case Online = 'online';
}
