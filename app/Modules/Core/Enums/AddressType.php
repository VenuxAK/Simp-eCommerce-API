<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible Address type values.
 */
enum AddressType: string
{
    case Shipping = 'shipping';
    case Billing = 'billing';
}
