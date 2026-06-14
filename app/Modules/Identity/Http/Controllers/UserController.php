<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Core\Traits\StoreScope;
use App\Modules\Identity\Http\Requests\StoreUserRequest;
use App\Modules\Identity\Http\Requests\UpdateUserRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Repositories\UserRepository;
use App\Modules\Identity\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    use ApiResponse, StoreScope;

    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $query = $this->userRepository->query()->orderBy('name');

        if (request()->user()->isStoreOwner()) {
            $query->where('store_id', request()->user()->store_id);
        }

        return UserResource::collection($query->paginate(20));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (request()->user()->isStoreOwner()) {
            $data['store_id'] = request()->user()->store_id;
        }

        $user = $this->userService->createUser($data);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        if (request()->user()->isStoreOwner() && $user->store_id !== request()->user()->store_id) {
            abort(404);
        }

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        if (request()->user()->isStoreOwner() && $user->store_id !== request()->user()->store_id) {
            abort(404);
        }

        $this->userService->updateUser($user, $request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user): JsonResponse
    {
        if (request()->user()->isStoreOwner() && $user->store_id !== request()->user()->store_id) {
            abort(404);
        }

        $error = $this->userService->canDelete(request()->user(), $user);

        if ($error) {
            return $this->respondError($error);
        }

        $this->userService->deleteUser($user);

        return $this->respondMessage('User deleted.');
    }
}
