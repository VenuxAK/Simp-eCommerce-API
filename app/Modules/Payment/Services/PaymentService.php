<?php

namespace App\Modules\Payment\Services;

use App\Modules\Core\Enums\PaymentMethod;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Enums\PaymentGatewayType;
use App\Modules\Payment\Gateways\MMPayGateway;
use App\Modules\Sales\Services\InvoiceNumberGenerator;
use Illuminate\Support\Collection;

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
            PaymentMethod::MMPay => app(MMPayGateway::class),
            default => throw new \InvalidArgumentException("Unsupported payment method: {$method->value}"),
        };
    }

    /**
     * Create a payment intent for the given cart total and method.
     * Returns gateway-specific data (client_secret, QR code, etc.).
     */
    public function createIntent(float $totalAmount, PaymentMethod $method, array $metadata): array
    {
        $gateway = $this->resolve($method);

        $orderNumber = $metadata['temp_order_number'] ?? $this->numberGenerator->generateOrderNumber();

        $result = $gateway->createIntent(
            amount: $totalAmount,
            currency: 'MMK',
            metadata: array_merge($metadata, ['order_number' => $orderNumber]),
        );

        return $result;
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
     * Handle a webhook event from a payment gateway.
     */
    public function handleWebhook(PaymentGatewayType $gateway, string $payload, string $signature): void
    {
        $gatewayImpl = match ($gateway) {
            PaymentGatewayType::MMPay => app(MMPayGateway::class),
            PaymentGatewayType::Stripe => throw new \RuntimeException('Stripe not yet implemented'),
            default => throw new \InvalidArgumentException("Unknown gateway: {$gateway->value}"),
        };

        if (! $gatewayImpl->verifyWebhook($payload, $signature)) {
            throw new \InvalidArgumentException('Invalid webhook signature');
        }

        $event = json_decode($payload, true);

        match ($gateway) {
            PaymentGatewayType::MMPay => $this->handleMMPayEvent($event),
            PaymentGatewayType::Stripe => throw new \RuntimeException('Stripe webhook not yet implemented'),
            default => null,
        };
    }

    /**
     * Process an incoming MMPay webhook event.
     */
    private function handleMMPayEvent(array $event): void
    {
        $transactionId = $event['transactionId'] ?? null;
        $newStatus = match ($event['status'] ?? '') {
            'success' => 'paid',
            'failed' => 'failed',
            'expired' => 'expired',
            default => null,
        };

        if (! $transactionId || ! $newStatus) {
            return;
        }

        // Find the payment transaction and update its status.
        $tx = \App\Modules\Payment\Models\PaymentTransaction::where('transaction_id', $transactionId)->first();

        if (! $tx) {
            return;
        }

        // Update the payment record.
        $payment = \App\Modules\Sales\Models\Payment::find($tx->payment_id);
        if ($payment) {
            $payment->update([
                'gateway_status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? now() : $payment->paid_at,
                'gateway_response' => $event,
            ]);
        }

        // If paid, update the invoice status.
        if ($newStatus === 'paid' && $payment && $payment->order && $payment->order->invoice) {
            $payment->order->invoice->update(['status' => 'paid']);
        }

        $tx->update([
            'gateway_status' => $newStatus,
            'response_data' => $event,
        ]);
    }
}
