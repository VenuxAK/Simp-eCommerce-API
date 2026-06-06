<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Http\Requests\CustomerLoginRequest;
use App\Modules\Customer\Http\Requests\RegisterCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Repositories\CustomerRepository;
use App\Modules\Store\Repositories\StoreRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Storefront customer authentication — session-based, distinct from staff auth.
 *
 * Uses the 'customer' auth guard (separate session/table from internal users).
 * Customers can register, log in, and log out via session-based auth.
 * Walk-in POS customers are created without passwords via the CRM instead.
 */
class CustomerAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly StoreRepository $storeRepository,
    ) {}

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Assign store_id from the X-Store header sent by the storefront.
        $storeSlug = $request->header('X-Store');
        if ($storeSlug) {
            $store = $this->storeRepository->findBySlug($storeSlug);
            if ($store) {
                $data['store_id'] = $store->id;
            }
        }

        $customer = $this->customerRepository->create($data);

        // Session login for Laravel-based storefronts; API clients skip this.
        if ($request->hasSession()) {
            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();
        }

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {

        $customer = $this->customerRepository->findByEmail($request->email);

        // password is nullable — walk-in customers created via CRM have no password.
        if (! $customer || ! $customer->password || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($request->hasSession()) {
            Auth::guard('customer')->login($customer);
            $request->session()->regenerate();
        }

        return $this->respond([
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->hasSession()) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->respondMessage('Logged out.');
    }
}
