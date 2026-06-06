<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\HandlesPasswordUpdate;
use App\Modules\Identity\Http\Requests\UpdateProfileRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * Handles Profile-related API requests.
 */
class ProfileController extends Controller
{
    use HandlesPasswordUpdate;

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        $this->handlePasswordUpdate($data, $request);

        $user->update($data);

        return new UserResource($user);
    }
}
