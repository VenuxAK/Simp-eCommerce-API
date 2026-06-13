<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Customer\Http\Requests\CustomerForgotPasswordRequest;
use App\Modules\Customer\Http\Requests\CustomerResetPasswordRequest;
use App\Modules\Customer\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Password;

class CustomerForgotPasswordController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CustomerRepository $customerRepository,
    ) {}

    public function sendResetLink(CustomerForgotPasswordRequest $request)
    {
        $customer = $this->customerRepository->findByEmail($request->email);

        if (! $customer || ! $customer->password) {
            return $this->respondMessage('If the account exists and uses email login, a reset link has been sent.');
        }

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email'),
        );

        return $status === Password::RESET_LINK_SENT
            ? $this->respondMessage('If the account exists and uses email login, a reset link has been sent.')
            : $this->respondError(__($status), 422);
    }

    public function reset(CustomerResetPasswordRequest $request)
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            },
        );

        return $status === Password::PASSWORD_RESET
            ? $this->respondMessage(__($status))
            : $this->respondError(__($status), 422);
    }
}
