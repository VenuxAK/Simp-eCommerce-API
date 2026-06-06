<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible Shipment method values.
 */
enum ShipmentMethod: string
{
    case Cod = 'cod';
    case Standard = 'standard';
    case Express = 'express';
}
