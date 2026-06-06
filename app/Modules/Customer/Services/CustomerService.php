<?php

namespace App\Modules\Customer\Services;

use App\Modules\Core\Traits\StoreScope;
use App\Modules\Customer\Models\Customer;

/**
 * Customer CRUD and data access, scoped by store for multi-tenant isolation.
 *
 * Search uses raw LOWER() for case-insensitive matching across name, email,
 * and phone — useful for POS staff quickly finding walk-in customers.
 * Order history eager-loads related invoice/payment data to avoid N+1 on
 * the customer detail view.
 */
class CustomerService
{
    use StoreScope;

    /**
     * Search and paginate customers within the current store scope.
     *
     * Uses LOWER() for case-insensitive search since POS staff often
     * type names/emails without worrying about case. withCount('orders')
     * enriches each row for display without a separate count query.
     */
    public function listCustomers(?string $search, int $perPage = 20)
    {
        $customers = Customer::withCount('orders')
            ->when(fn ($q) => $this->scopeByStore($q))
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(phone) LIKE LOWER(?)', ['%'.$search.'%']);
            }))
            ->orderBy('name')
            ->paginate($perPage);

        return $customers;
    }

    public function createCustomer(array $data): Customer
    {
        return Customer::create($data);
    }

    public function updateCustomer(Customer $customer, array $data): void
    {
        $customer->update($data);
    }

    public function deleteCustomer(Customer $customer): void
    {
        $customer->delete();
    }

    /**
     * Paginated order history for a customer with key relationships preloaded.
     *
     * Eager-loads items → variant → product chain (for line-item display),
     * plus payment and invoice for financial context on the order detail page.
     */
    public function getCustomerOrders(Customer $customer)
    {
        return $customer->orders()
            ->with(['items.variant.product', 'payment', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
