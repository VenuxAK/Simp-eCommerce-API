<?php

namespace App\Modules\Cash\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Http\Requests\CloseCashSessionRequest;
use App\Modules\Cash\Http\Requests\OpenCashSessionRequest;
use App\Modules\Cash\Http\Resources\CashSessionResource;
use App\Modules\Cash\Repositories\CashSessionRepository;
use App\Modules\Cash\Services\CashSessionService;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use Illuminate\Http\JsonResponse;
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

    public function __construct(
        private readonly CashSessionService $cashSessionService,
        private readonly CashSessionRepository $cashSessionRepository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $sessions = $this->cashSessionRepository->query()
            ->with('user')
            ->when(fn ($q) => $this->scopeByStore($q))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return CashSessionResource::collection($sessions);
    }

    public function active(): JsonResponse|CashSessionResource
    {
        $session = $this->cashSessionRepository->findActiveByStore($this->resolveStoreId());

        if (! $session) {
            return $this->respond(['data' => null]);
        }

        return new CashSessionResource($session);
    }

    public function open(OpenCashSessionRequest $request): JsonResponse
    {
        $existing = $this->cashSessionRepository->findActiveByStore($this->resolveStoreId());

        if ($existing) {
            return $this->respondError(__('messages.cash.session_already_open'));
        }

        $data = $request->validated();

        $session = $this->cashSessionRepository->create($this->mergeStoreId([
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'opening_balance' => $data['opening_balance'],
            'notes' => $data['notes'] ?? null,
        ]));

        return (new CashSessionResource($session))->response()->setStatusCode(201);
    }

    public function close(CloseCashSessionRequest $request): JsonResponse
    {
        $session = $this->cashSessionRepository->findActiveByStore($this->resolveStoreId());

        if (! $session) {
            return $this->respondError(__('messages.cash.no_open_session'));
        }

        $data = $request->validated();

        $settlement = $this->cashSessionService->calculateSettlement($session, now());
        $difference = $data['closing_balance'] - $settlement['expected_balance'];

        $this->cashSessionRepository->update($session, [
            'closed_at' => now(),
            'closing_balance' => $data['closing_balance'],
            'expected_balance' => $settlement['expected_balance'],
            'difference' => $difference,
            'notes' => $data['notes'] ?? $session->notes,
        ]);

        return $this->respond(new CashSessionResource($session));
    }
}
