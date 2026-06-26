<?php

namespace App\Modules\Core\Enums;

/**
 * How payment was tendered — cash (in-person POS) or bank transfer (online).
 *
 * Extensible for additional gateways (card, mobile money) as they're integrated.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Stripe = 'stripe';
}
