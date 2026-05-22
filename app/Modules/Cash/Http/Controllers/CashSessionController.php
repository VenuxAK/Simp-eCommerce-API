<?php

namespace App\Modules\Cash\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Http\Resources\CashSessionResource;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Sales\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles CashSession-related API requests.
 */
class CashSessionController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $sessions = CashSession::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return CashSessionResource::collection($sessions);
    }

    public function active(): JsonResponse|CashSessionResource
    {
        $session = CashSession::whereNull('closed_at')->first();
        if (!$session) {
            return $this->respond(['data' => null]);
        }
        return new CashSessionResource($session);
    }

    public function open(Request $request): JsonResponse
    {
        $existing = CashSession::whereNull('closed_at')->first();
        if ($existing) {
            return $this->respondError('A cash session is already open.');
        }

        $data = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = CashSession::create([
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'opening_balance' => $data['opening_balance'],
            'notes' => $data['notes'] ?? null,
        ]);

        return new CashSessionResource($session)->response()->setStatusCode(201);
    }

    public function close(Request $request): JsonResponse
    {
        $session = CashSession::whereNull('closed_at')->first();
        if (!$session) {
            return $this->respondError('No open cash session.');
        }

        $data = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cashOrdersTotal = (float) Order::whereBetween('created_at', [$session->opened_at, now()])
            ->where('status', 'completed')
            ->whereHas('payment', fn($q) => $q->where('method', 'cash'))
            ->sum('total_amount');

        $expectedBalance = $session->opening_balance + $cashOrdersTotal;
        $difference = $data['closing_balance'] - $expectedBalance;

        $session->update([
            'closed_at' => now(),
            'closing_balance' => $data['closing_balance'],
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'notes' => $data['notes'] ?? $session->notes,
        ]);

        return $this->respond(new CashSessionResource($session));
    }
}
