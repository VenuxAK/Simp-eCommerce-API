<?php

namespace App\Modules\Core\Enums;

/**
 * How a discount is applied — percentage off the total, or a fixed amount reduction.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
