<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Identity\Http\Requests\StoreUserRequest;
use App\Modules\Identity\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    use ApiResponse;

    public function index(): AnonymousResourceCollection
    {
        $users = User::orderBy('name')->paginate(20);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);

        return new UserResource($user)->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return new UserResource($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $currentUser = request()->user();

        if ($user->id === $currentUser->id) {
            return $this->respondError('Cannot delete yourself.');
        }

        if ($user->isAdmin() && $currentUser->isAdmin()) {
            return $this->respondError('Cannot delete another admin user.');
        }

        // Check for orders linked to this user before allowing deletion.
        $orderCount = Order::where('user_id', $user->id)->count();

        if ($orderCount > 0) {
            return $this->respondError("Cannot delete user: {$orderCount} order(s) are linked to this user.");
        }

        $user->delete();

        return $this->respondMessage('User deleted.');
    }
}
