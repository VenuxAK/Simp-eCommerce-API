<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Http\Requests\StoreAddressRequest;
use App\Modules\Customer\Http\Requests\UpdateAddressRequest;
use App\Modules\Customer\Http\Resources\AddressResource;
use App\Modules\Customer\Models\Address;
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
    use ApiResponse;

    public function index(Request $request): AnonymousResourceCollection
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();

        return AddressResource::collection($addresses);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $customer = $request->user();

        $isFirst = ! $customer->addresses()->exists();
        $data = $request->validated();
        // Auto-default the first address; otherwise respect the request if set.
        $data['is_default'] = $isFirst || ($data['is_default'] ?? false);

        // Demote any existing default before promoting the new one.
        if ($data['is_default']) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create($data);

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
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return new AddressResource($address);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwner($request, $address);

        $address->delete();

        return $this->respondMessage('Address deleted.');
    }

    public function setDefault(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwner($request, $address);

        // Unset all other defaults, set this one.
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return $this->respond(new AddressResource($address));
    }

    /**
     * Ensure the authenticated customer owns the targeted address.
     *
     * Direct comparison on customer_id avoids an extra query to load
     * the parent relationship simply for ownership verification.
     */
    private function authorizeOwner(Request $request, Address $address): void
    {
        if ($address->customer_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }
    }
}
