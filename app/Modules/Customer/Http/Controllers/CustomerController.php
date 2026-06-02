<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Customer\Http\Requests\StoreCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use App\Modules\Sales\Http\Resources\OrderResource;
use App\Modules\Store\Models\Store;
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

    public function index(): AnonymousResourceCollection
    {
        $customers = Customer::withCount('orders')
            ->when(fn($q) => $this->scopeByStore($q))
            ->when(request('search'), fn($q) => $q->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . request('search') . '%'])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ['%' . request('search') . '%'])
                    ->orWhereRaw('LOWER(phone) LIKE LOWER(?)', ['%' . request('search') . '%']);
            }))
            ->orderBy('name')
            ->paginate(20);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data = $this->mergeStoreId($data);

        // Fallback: read X-Store header for root users without a store selector.
        if (empty($data['store_id'])) {
            $store = Store::where('slug', request()->header('X-Store'))->first();
            if ($store) {
                $data['store_id'] = $store->id;
            }
        }

        $customer = Customer::create($data);

        return new CustomerResource($customer)->response()->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->loadCount('orders'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): CustomerResource
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return $this->respondMessage('Customer deleted.');
    }

    public function orders(Customer $customer): AnonymousResourceCollection
    {
        $orders = $customer->orders()
            ->with(['items.variant.product', 'payment', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return OrderResource::collection($orders);
    }
}
