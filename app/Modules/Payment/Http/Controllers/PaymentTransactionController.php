<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\PaginatesResults;
use App\Modules\Payment\Http\Resources\PaymentTransactionResource;
use App\Modules\Payment\Repositories\PaymentTransactionRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentTransactionController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function __construct(
        private readonly PaymentTransactionRepository $transactionRepo,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        // Enforce user authorization (e.g. must have permission to view audits or orders)
        if (request()->user() && ! request()->user()->hasPermissionTo('orders.view')) {
            abort(403);
        }

        $transactions = $this->transactionRepo->paginateFiltered(
            $this->resolvePerPage()
        );

        return PaymentTransactionResource::collection($transactions);
    }
}
