<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\AuthorizesOwnership;
use App\Modules\Customer\Http\Requests\StoreAddressRequest;
use App\Modules\Customer\Http\Requests\UpdateAddressRequest;
use App\Modules\Customer\Http\Resources\AddressResource;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Repositories\AddressRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Customer address book management.
 *
 * All operations are scoped to the authenticated customer.
 */
class AddressController extends Controller
{
    use ApiResponse, AuthorizesOwnership;

    public function __construct(
        private readonly AddressRepository $addressRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $addresses = $this->addressRepository->findByCustomer($request->user()->id)
            ->sortByDesc('is_default');

        return AddressResource::collection($addresses);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $customer = $request->user();

        $isFirst = $this->addressRepository->findByCustomer($customer->id)->isEmpty();
        $data = $request->validated();
        // Auto-default the first address; otherwise respect the request if set.
        $data['is_default'] = $isFirst || ($data['is_default'] ?? false);

        // Demote any existing default before promoting the new one.
        if ($data['is_default']) {
            $this->addressRepository->clearDefaults($customer->id);
        }

        $address = $this->addressRepository->create([
            ...$data,
            'customer_id' => $customer->id,
        ]);

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function show(Request $request, Address $address): AddressResource
    {
        $this->authorizeOwner($request, $address);

        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address): AddressResource
    {
        $this->authorizeOwner($request, $address);

        $data = $request->validated();

        // Demote all other defaults first to maintain the single-default invariant.
        if ($data['is_default'] ?? false) {
            $this->addressRepository->clearDefaults($request->user()->id, $address->id);
        }

        $this->addressRepository->update($address, $data);

        return new AddressResource($address);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwner($request, $address);

        $this->addressRepository->delete($address);

        return $this->respondMessage('Address deleted.');
    }

    public function setDefault(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwner($request, $address);

        // Unset all other defaults, set this one.
        $this->addressRepository->clearDefaults($request->user()->id);
        $this->addressRepository->update($address, ['is_default' => true]);

        return $this->respond(new AddressResource($address));
    }

    private function authorizeOwner(Request $request, Address $address): void
    {
        $this->authorizeOwnership($request, $address);
    }
}
