<?php

namespace App\Modules\Core\Enums;

/**
 * Shipping options available to customers at checkout.
 *
 * Cod is cash-on-delivery; Standard/Express are prepaid carrier methods.
 */
enum ShipmentMethod: string
{
    case Cod = 'cod';
    case Standard = 'standard';
    case Express = 'express';
}
