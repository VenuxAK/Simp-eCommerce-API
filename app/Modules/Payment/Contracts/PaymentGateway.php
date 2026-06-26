<?php

namespace App\Modules\Payment\Contracts;

/**
 * Contract that every payment gateway must implement.
 * Supports creating payment intents, checking status, refunding, and webhook verification.
 */
interface PaymentGateway
{
    /**
     * Create a payment request (intent, order, or QR code depending on gateway).
     *
     * @param  float  $amount    Amount in MMK.
     * @param  string $currency  ISO 4217 currency code (e.g. MMK, USD).
     * @param  array  $metadata  Order context (order_number, store_id, etc.).
     * @return array             Gateway-specific response (client_secret, QR code, etc.).
     */
    public function createIntent(float $amount, string $currency, array $metadata): array;

    /**
     * Check the current status of a payment by its gateway transaction ID.
     *
     * @param  string $transactionId  Gateway transaction ID.
     * @return string                  Status string (pending, paid, failed, expired).
     */
    public function checkStatus(string $transactionId): string;

    /**
     * Refund a payment (full or partial).
     *
     * @param  string   $transactionId  Gateway transaction ID.
     * @param  float|null $amount       Amount to refund (null = full).
     * @return array                    Refund result.
     */
    public function refund(string $transactionId, ?float $amount = null): array;

    /**
     * Verify the authenticity of an incoming webhook payload.
     *
     * @param  string $payload    Raw request body.
     * @param  string $signature  Signature header value.
     * @return bool               True if signature is valid.
     */
    public function verifyWebhook(string $payload, string $signature): bool;
}
