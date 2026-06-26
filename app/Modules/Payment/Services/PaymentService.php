<?php

namespace App\Modules\Payment\Services;

use App\Modules\Core\Enums\PaymentMethod;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Enums\PaymentGatewayType;
use App\Modules\Payment\Gateways\MMPayGateway;
use App\Modules\Payment\Gateways\StripeGateway;
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
            $totalAmount,
            'MMK',
            array_merge($metadata, ['order_number' => $orderNumber]),
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
            PaymentGatewayType::Stripe => app(StripeGateway::class),
            default => throw new \InvalidArgumentException("Unknown gateway: {$gateway->value}"),
        };

        if (! $gatewayImpl->verifyWebhook($payload, $signature)) {
            throw new \InvalidArgumentException('Invalid webhook signature');
        }

        $event = json_decode($payload, true);

        match ($gateway) {
            PaymentGatewayType::MMPay => $this->handleMMPayEvent($event),
            PaymentGatewayType::Stripe => $this->handleStripeEvent($event),
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

        $this->updatePaymentByTransaction($transactionId, $newStatus, $event);
    }

    /**
     * Process an incoming Stripe webhook event.
     */
    private function handleStripeEvent(array $event): void
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

        $this->updatePaymentByTransaction($intentId, $newStatus, $event);
    }

    /**
     * Find a payment by transaction ID and update its status.
     * Also updates the invoice status when payment succeeds.
     */
    private function updatePaymentByTransaction(string $transactionId, string $newStatus, array $event): void
    {
        $payment = \App\Modules\Sales\Models\Payment::where('transaction_id', $transactionId)->first();

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

        // Update the transaction record if one exists.
        \App\Modules\Payment\Models\PaymentTransaction::where('transaction_id', $transactionId)
            ->update(['gateway_status' => $newStatus, 'response_data' => $event]);
    }

}
