<?php

namespace App\Modules\Core\Enums;

/**
 * What a discount applies to — the entire cart, a specific category, or a specific product.
 *
 * Determines how the discount engine matches and applies the rule.
 */
enum DiscountScope: string
{
    case All = 'all';
    case Category = 'category';
    case Product = 'product';
}
