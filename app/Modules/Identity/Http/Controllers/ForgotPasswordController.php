<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Identity\Http\Requests\ForgotPasswordRequest;
use App\Modules\Identity\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use ApiResponse;

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $status = Password::broker('users')->sendResetLink(
            $request->only('email'),
        );

        return $status === Password::RESET_LINK_SENT
            ? $this->respondMessage(__($status))
            : $this->respondError(__($status), 422);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::broker('users')->reset(
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
