<?php

namespace App\Modules\Payment\Enums;

/**
 * Supported payment gateway providers.
 */
enum PaymentGatewayType: string
{
    case MMPay = 'mmpay';
    case Stripe = 'stripe';
    case Cod = 'cod';
}
