<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Contracts\PaymentGateway;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Stripe payment gateway — processes card payments via PaymentIntent.
 *
 * MMK is not supported by Stripe as a presentment currency, so amounts
 * are converted to USD internally using a configurable exchange rate.
 */
class StripeGateway implements PaymentGateway
{
    private const MMK_TO_USD = 2100;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent. Accepts MMK amount, converts to USD.
     */
    public function createIntent(float $amountMMK, string $currency, array $metadata): array
    {
        $amountUSD = round($amountMMK / self::MMK_TO_USD, 2);

        $intent = PaymentIntent::create([
            'amount' => (int) ($amountUSD * 100),
            'currency' => 'usd',
            'metadata' => array_merge($metadata, [
                'amount_mmk' => (string) $amountMMK,
            ]),
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return [
            'gateway' => 'stripe',
            'transaction_id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'status' => $intent->status,
        ];
    }

    /**
     * Retrieve and check the status of a PaymentIntent.
     */
    public function checkStatus(string $intentId): string
    {
        $intent = PaymentIntent::retrieve($intentId);

        return $intent->status;
    }

    /**
     * Refund a PaymentIntent (full or partial).
     */
    public function refund(string $intentId, ?float $amount = null): array
    {
        $params = ['payment_intent' => $intentId];
        if ($amount !== null) {
            $params['amount'] = (int) (round($amount / self::MMK_TO_USD, 2) * 100);
        }

        $refund = Refund::create($params);

        return [
            'transaction_id' => $refund->id,
            'status' => $refund->status,
        ];
    }

    /**
     * Verify a Stripe webhook signature.
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        try {
            Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));

            return true;
        } catch (SignatureVerificationException) {
            return false;
        } catch (\UnexpectedValueException) {
            return false;
        }
    }
}
