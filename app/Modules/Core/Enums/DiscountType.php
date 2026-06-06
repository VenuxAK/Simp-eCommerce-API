<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible Discount type values.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
