<?php

namespace App\Modules\Core\Enums;

/**
 * Represents the scope a discount applies to.
 */
enum DiscountScope: string
{
    case All = 'all';
    case Category = 'category';
    case Product = 'product';
}
