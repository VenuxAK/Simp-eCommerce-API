<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashSessionController extends Controller
{
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
            return response()->json(['data' => null]);
        }
        return new CashSessionResource($session);
    }

    public function open(Request $request): JsonResponse
    {
        $existing = CashSession::whereNull('closed_at')->first();
        if ($existing) {
            return response()->json(['message' => 'A cash session is already open.'], 422);
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
            return response()->json(['message' => 'No open cash session.'], 422);
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

        return response()->json(new CashSessionResource($session));
    }
}
