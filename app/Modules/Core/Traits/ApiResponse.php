<?php

namespace App\Modules\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * ApiResponse helper methods.
 */
trait ApiResponse
{
    protected function respond(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($data, $status, $headers);
    }

    protected function respondMessage(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function respondError(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function respondCreated(mixed $data): JsonResponse
    {
        return response()->json($data, 201);
    }

    protected function respondNoContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
