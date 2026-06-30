<?php

namespace App\Modules\Customer\Services;

use App\Modules\Core\Traits\StoreScope;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Repositories\CustomerRepository;

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

    public function __construct(
        private readonly CustomerRepository $customerRepository,
    ) {}

    /**
     * Search and paginate customers within the current store scope.
     *
     * Uses LOWER() for case-insensitive search since POS staff often
     * type names/emails without worrying about case. withCount('orders')
     * enriches each row for display without a separate count query.
     */
    public function listCustomers(?string $search, int $perPage = 20)
    {
        return $this->customerRepository->paginateFiltered(
            storeId: $this->resolveStoreId(),
            search: $search,
            perPage: $perPage,
        );
    }

    public function createCustomer(array $data): Customer
    {
        return $this->customerRepository->create($data);
    }

    public function updateCustomer(Customer $customer, array $data): void
    {
        $this->customerRepository->update($customer, $data);
    }

    public function deleteCustomer(Customer $customer): void
    {
        $this->customerRepository->delete($customer);
    }

    /**
     * Paginated order history for a customer with key relationships preloaded.
     *
     * Eager-loads items → variant → product chain (for line-item display),
     * plus payment and invoice for financial context on the order detail page.
     */
    public function getCustomerOrders(Customer $customer, int $perPage = 20)
    {
        return $customer->orders()
            ->with(['items.variant.product', 'payment', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
