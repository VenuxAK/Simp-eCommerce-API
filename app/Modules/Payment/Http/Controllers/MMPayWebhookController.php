<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Payment\Enums\PaymentGatewayType;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles webhook callbacks from MyanMyanPay.
 * Updates payment and invoice status on success/failure/expiry.
 */
class MMPayWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-MPay-Signature', '');

        try {
            $this->paymentService->handleWebhook(
                PaymentGatewayType::MMPay,
                $payload,
                $signature,
            );

            return $this->respondMessage('OK');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->respondError('Internal error.', 500);
        }
    }
}
