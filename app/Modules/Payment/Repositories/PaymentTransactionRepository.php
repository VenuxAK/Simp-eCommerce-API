<?php

namespace App\Modules\Payment\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentTransactionRepository extends Repository
{
    protected function model(): string
    {
        return PaymentTransaction::class;
    }

    public function paginateFiltered(int $perPage = 20): LengthAwarePaginator
    {
        return PaymentTransaction::orderBy('created_at', 'desc')
            ->paginate($this->clampPerPage($perPage));
    }
}
