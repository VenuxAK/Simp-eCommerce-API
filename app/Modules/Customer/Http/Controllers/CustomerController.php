<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Http\Requests\StoreCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $customers = Customer::withCount('orders')
            ->when(request('search'), fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('email', 'like', '%' . request('search') . '%')
                    ->orWhere('phone', 'like', '%' . request('search') . '%');
            }))
            ->orderBy('name')
            ->paginate(20);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

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
