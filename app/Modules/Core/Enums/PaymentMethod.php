<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible PaymentMethod values.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
}
