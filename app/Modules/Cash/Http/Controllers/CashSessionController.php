<?php

namespace App\Modules\Cash\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Http\Resources\CashSessionResource;
use App\Modules\Cash\Models\CashSession;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Sales\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Cash drawer session management.
 *
 * Each session is scoped to a store so store admins only see
 * their own register activity. Sessions are created with
 * the staff user's store_id via mergeStoreId().
 */
class CashSessionController extends Controller
{
    use ApiResponse, StoreScope;

    public function index(): AnonymousResourceCollection
    {
        $sessions = CashSession::with('user')
            ->when(fn($q) => $this->scopeByStore($q))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return CashSessionResource::collection($sessions);
    }

    public function active(): JsonResponse|CashSessionResource
    {
        $query = CashSession::whereNull('closed_at');
        $this->scopeByStore($query);
        $session = $query->first();

        if (!$session) {
            return $this->respond(['data' => null]);
        }
        return new CashSessionResource($session);
    }

    public function open(Request $request): JsonResponse
    {
        $existingQuery = CashSession::whereNull('closed_at');
        $this->scopeByStore($existingQuery);
        $existing = $existingQuery->first();

        if ($existing) {
            return $this->respondError('A cash session is already open for this store.');
        }

        $data = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = CashSession::create($this->mergeStoreId([
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'opening_balance' => $data['opening_balance'],
            'notes' => $data['notes'] ?? null,
        ]));

        return new CashSessionResource($session)->response()->setStatusCode(201);
    }

    public function close(Request $request): JsonResponse
    {
        $sessionQuery = CashSession::whereNull('closed_at');
        $this->scopeByStore($sessionQuery);
        $session = $sessionQuery->first();

        if (!$session) {
            return $this->respondError('No open cash session for this store.');
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
