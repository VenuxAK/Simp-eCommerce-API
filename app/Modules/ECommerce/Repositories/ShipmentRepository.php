<?php

namespace App\Modules\ECommerce\Repositories;

use App\Modules\Core\Repositories\Repository;
use App\Modules\ECommerce\Models\Shipment;

/**
 * Encapsulates Eloquent data access for Shipment records.
 *
 * Basic CRUD is inherited from the base Repository; this class
 * adds order-scoped lookups needed during checkout and
 * order-status transitions.
 *
 * @extends Repository<Shipment>
 */
class ShipmentRepository extends Repository
{
    protected function model(): string
    {
        return Shipment::class;
    }

    /**
     * Find the shipment associated with a given order.
     */
    public function findByOrder(int $orderId): ?Shipment
    {
        return Shipment::where('order_id', $orderId)->first();
    }
}
