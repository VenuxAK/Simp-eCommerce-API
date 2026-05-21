<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $logs = AuditLog::with('user')
            ->when(request('action'), fn($q) => $q->where('action', request('action')))
            ->when(request('model'), fn($q) => $q->where('model_type', request('model')))
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return \App\Http\Resources\AuditLogResource::collection($logs);
    }
}
