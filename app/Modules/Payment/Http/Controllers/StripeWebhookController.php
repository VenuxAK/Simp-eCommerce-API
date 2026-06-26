<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Stripe webhook events.
 * Updates payment and invoice status on confirmed payment intents.
 */
class StripeWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $this->paymentService->handleStripeWebhook($payload, $signature);

            return $this->respondMessage('OK');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->respondError('Internal error.', 500);
        }
    }
}
