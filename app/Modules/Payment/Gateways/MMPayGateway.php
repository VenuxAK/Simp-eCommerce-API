<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;

/**
 * MyanMyanPay gateway — processes MMQR wallet payments (KBZPay, WavePay, etc.).
 *
 * @see https://docs.myanmyanpay.com
 */
class MMPayGateway implements PaymentGateway
{
    private string $apiKey;

    private string $baseUrl;

    private string $secret;

    public function __construct()
    {
        $this->apiKey = config('services.mmpay.api_key', '');
        $this->baseUrl = config('services.mmpay.base_url', 'https://api.myanmyanpay.com/v1');
        $this->secret = config('services.mmpay.webhook_secret', '');
    }

    /**
     * Create a MMPay payment transaction (QR code) for the given amount.
     * Returns the QR code and transaction ID for the frontend.
     */
    public function createIntent(float $amount, string $currency, array $metadata): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/payments/create", [
                'amount' => (int) $amount,
                'currency' => 'MMK',
                'orderId' => $metadata['order_number'] ?? 'ORD-' . uniqid(),
            ]);

        $response->throw();

        return [
            'gateway' => 'mmpay',
            'transaction_id' => $response['transactionId'],
            'qr_code' => $response['qrCode'],
            'qr_url' => $response['qrUrl'],
            'status' => $response['status'] ?? 'pending',
        ];
    }

    /**
     * Check payment status by transaction ID.
     */
    public function checkStatus(string $transactionId): string
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/payments/{$transactionId}");

        $response->throw();

        return $response['status'] ?? 'unknown';
    }

    /**
     * Refund a payment (full or partial).
     */
    public function refund(string $transactionId, ?float $amount = null): array
    {
        $payload = $amount !== null ? ['amount' => (int) $amount] : [];

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/payments/{$transactionId}/refund", $payload);

        $response->throw();

        return [
            'transaction_id' => $response['transactionId'],
            'status' => $response['status'],
        ];
    }

    /**
     * Verify webhook signature via HMAC-SHA256.
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->secret);

        return hash_equals($expected, $signature);
    }
}
