<?php

namespace App\Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Traits\ApiResponse;
use App\Modules\Store\Models\Store;
use App\Modules\Identity\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\System\Services\BackupService;
use Illuminate\Http\JsonResponse;

class SystemDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function summary(): JsonResponse
    {
        $storesCount = Store::count();
        $usersCount = User::count();
        
        $backupsCount = count($this->backupService->list());
        
        $recentAuditLogs = AuditLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return $this->respond([
            'total_stores' => $storesCount,
            'total_users' => $usersCount,
            'total_backups' => $backupsCount,
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }
}
