<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Customer\Http\Requests\StoreCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Sales\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Customer CRM — staff dashboard and storefront management.
 *
 * List queries are scoped by store (customers now have store_id).
 * New customers created via admin get store_id from X-Store header
 * or default to the staff user's assigned store.
 */
class CustomerController extends Controller
{
    use ApiResponse, StoreScope;

    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CustomerResource::collection(
            $this->customerService->listCustomers(request('search')),
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer(
            $this->mergeStoreId($request->validated()),
        );

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->loadCount('orders'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $this->customerService->updateCustomer($customer, $request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->deleteCustomer($customer);

        return $this->respondMessage('Customer deleted.');
    }

    public function orders(Customer $customer): AnonymousResourceCollection
    {
        return OrderResource::collection(
            $this->customerService->getCustomerOrders($customer),
        );
    }
}
