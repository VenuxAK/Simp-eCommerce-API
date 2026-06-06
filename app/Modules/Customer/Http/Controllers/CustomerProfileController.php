<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\HandlesPasswordUpdate;
use App\Modules\Customer\Http\Requests\UpdateCustomerProfileRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

/**
 * Manage the authenticated customer's own profile.
 */
class CustomerProfileController extends Controller
{
    use HandlesPasswordUpdate;

    public function show(Request $request): CustomerResource
    {
        return new CustomerResource($request->user());
    }

    public function update(UpdateCustomerProfileRequest $request): CustomerResource
    {
        $customer = $request->user();
        $data = $request->validated();

        $this->handlePasswordUpdate($data, $request);

        $customer->update($data);

        return new CustomerResource($customer);
    }
}
