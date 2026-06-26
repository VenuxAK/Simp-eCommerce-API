<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\PaymentMethod;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\ECommerce\Models\CartItem;
use App\Modules\Payment\Http\Requests\CreatePaymentIntentRequest;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;

/**
 * Creates a payment intent / QR code for the customer's cart.
 * Called before POST /checkout so the customer can complete payment first.
 */
class PaymentIntentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function create(CreatePaymentIntentRequest $request): JsonResponse
    {
        $customer = $request->user();

        $cartItems = CartItem::where('customer_id', $customer->id)
            ->with('variant.product')
            ->get();

        if ($cartItems->isEmpty()) {
            return $this->respondError(__('messages.checkout.cart_empty'), 422);
        }

        $totalAmount = $cartItems->sum(fn ($item) =>
            ($item->variant->product->base_price + $item->variant->price_adjustment) * $item->quantity
        );

        $method = PaymentMethod::from($request->payment_method);

        $result = $this->paymentService->createIntent($totalAmount, $method, [
            'customer_id' => $customer->id,
        ]);

        // Persist the transaction for later verification and webhook reconciliation.
        PaymentTransaction::create([
            'gateway' => $result['gateway'],
            'transaction_id' => $result['transaction_id'],
            'gateway_status' => $result['status'] ?? 'pending',
            'amount' => $totalAmount,
            'currency' => 'MMK',
            'request_data' => $result,
        ]);

        return $this->respond($result);
    }
}
