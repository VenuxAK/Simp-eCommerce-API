<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Http\Requests\RegisterCustomerRequest;
use App\Modules\Customer\Http\Resources\CustomerResource;
use App\Modules\Customer\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        $token = $customer->createToken('storefront-token', ['customer:*'], now()->addDays(7))->plainTextToken;

        return $this->respond([
            'token' => $token,
            'customer' => new CustomerResource($customer),
        ])->setStatusCode(201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !$customer->password || !Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $customer->tokens()->delete();
        $token = $customer->createToken('storefront-token', ['customer:*'], now()->addDays(7))->plainTextToken;

        return $this->respond([
            'token' => $token,
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->respondMessage('Logged out.');
    }
}
