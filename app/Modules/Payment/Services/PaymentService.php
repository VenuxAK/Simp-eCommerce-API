<?php

namespace App\Modules\Payment\Services;

use App\Modules\Core\Enums\PaymentMethod;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Gateways\StripeGateway;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Services\InvoiceNumberGenerator;

/**
 * Resolves the correct payment gateway and orchestrates payment operations.
 */
class PaymentService
{
    public function __construct(
        private readonly InvoiceNumberGenerator $numberGenerator,
    ) {}

    /**
     * Resolve a payment gateway implementation by payment method.
     */
    public function resolve(PaymentMethod $method): PaymentGateway
    {
        return match ($method) {
            PaymentMethod::Stripe => app(StripeGateway::class),
            default => throw new \InvalidArgumentException("Unsupported payment method: {$method->value}"),
        };
    }

    /**
     * Create a payment intent for the given cart total and method.
     */
    public function createIntent(float $totalAmount, PaymentMethod $method, array $metadata): array
    {
        $gateway = $this->resolve($method);

        $orderNumber = $metadata['temp_order_number'] ?? $this->numberGenerator->generateOrderNumber();

        return $gateway->createIntent(
            $totalAmount,
            'MMK',
            array_merge($metadata, ['order_number' => $orderNumber]),
        );
    }

    /**
     * Verify a payment intent still exists and is valid.
     */
    public function verifyIntent(PaymentMethod $method, string $transactionId, float $expectedAmount): bool
    {
        $gateway = $this->resolve($method);
        $status = $gateway->checkStatus($transactionId);

        return in_array($status, ['pending', 'succeeded', 'requires_payment_method', 'requires_confirmation']);
    }

    /**
     * Handle a Stripe webhook event.
     */
    public function handleStripeWebhook(string $payload, string $signature): void
    {
        $gateway = app(StripeGateway::class);

        if (! $gateway->verifyWebhook($payload, $signature)) {
            throw new \InvalidArgumentException('Invalid webhook signature');
        }

        $event = json_decode($payload, true);
        $this->processStripeEvent($event);
    }

    /**
     * Process an incoming Stripe webhook event.
     */
    private function processStripeEvent(array $event): void
    {
        $type = $event['type'] ?? '';
        $intent = $event['data']['object'] ?? [];
        $intentId = $intent['id'] ?? null;

        $newStatus = match ($type) {
            'payment_intent.succeeded' => 'paid',
            'payment_intent.payment_failed' => 'failed',
            default => null,
        };

        if (! $intentId || ! $newStatus) {
            return;
        }

        $payment = Payment::where('transaction_id', $intentId)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'gateway_status' => $newStatus,
            'paid_at' => $newStatus === 'paid' ? now() : $payment->paid_at,
            'gateway_response' => $event,
        ]);

        if ($newStatus === 'paid' && $payment->order && $payment->order->invoice) {
            $payment->order->invoice->update(['status' => 'paid']);
        }

        \App\Modules\Payment\Models\PaymentTransaction::where('transaction_id', $intentId)
            ->update(['gateway_status' => $newStatus, 'response_data' => $event]);
    }
}
