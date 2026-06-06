<?php

namespace App\Modules\Core\Enums;

/**
 * Purpose of a customer address — shipping, billing, or both (a single address
 * can serve both roles depending on how it's used at checkout).
 */
enum AddressType: string
{
    case Shipping = 'shipping';
    case Billing = 'billing';
}
