<?php

namespace App\Modules\Core\Traits;

use Illuminate\Http\Request;

trait HandlesPasswordUpdate
{
    public function handlePasswordUpdate(array &$data, Request $request): void
    {
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        } else {
            unset($data['password']);
        }
    }
}
