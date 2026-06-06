<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cash\Http\Resources\CashSessionResource;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Report\Services\DashboardService;
use App\Modules\Sales\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function summary(): JsonResponse
    {
        $data = $this->dashboardService->summary();

        return $this->respond([
            'today_sales' => $data['today_sales'],
            'today_orders_count' => $data['today_orders_count'],
            'active_session' => $data['active_session'] ? new CashSessionResource($data['active_session']) : null,
            'total_products' => $data['total_products'],
            'total_variants' => $data['total_variants'],
            'low_stock_count' => $data['low_stock_count'],
            'out_of_stock_count' => $data['out_of_stock_count'],
            'low_stock_variants' => $data['low_stock_variants'],
            'recent_orders' => OrderResource::collection($data['recent_orders']),
        ]);
    }
}
