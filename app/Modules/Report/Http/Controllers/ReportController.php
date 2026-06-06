<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Report\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sales analytics and reporting.
 *
 * All report queries are scoped by the current store
 * to ensure store admins only see their own data.
 */
class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function sales(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        return $this->respond(
            $this->reportService->sales($dateFrom, $dateTo),
        );
    }

    public function bestSellers(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $limit = (int) $request->input('limit', 20);

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'data' => $this->reportService->bestSellers($dateFrom, $dateTo, $limit),
        ]);
    }

    public function paymentMethods(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        return $this->respond([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'data' => $this->reportService->paymentMethods($dateFrom, $dateTo),
        ]);
    }
}
