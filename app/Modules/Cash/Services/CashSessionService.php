<?php

namespace App\Modules\Cash\Services;

use App\Modules\Cash\Models\CashSession;
use App\Modules\Sales\Repositories\OrderRepository;
use Carbon\Carbon;

class CashSessionService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    public function calculateSettlement(CashSession $session, Carbon $now): array
    {
        $cashOrdersTotal = (float) $this->orderRepository->query()
            ->whereBetween('created_at', [$session->opened_at, $now])
            ->where('status', 'completed')
            ->whereHas('payment', fn ($q) => $q->where('method', 'cash'))
            ->sum('total_amount');

        $expectedBalance = $session->opening_balance + $cashOrdersTotal;

        return [
            'cash_orders_total' => $cashOrdersTotal,
            'expected_balance' => $expectedBalance,
        ];
    }
}
