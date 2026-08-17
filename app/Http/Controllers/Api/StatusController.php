<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use Illuminate\Http\JsonResponse;
use App\Services\AsteriskService;

class StatusController extends Controller
{
    /**
     * Get call statuses and active calls count
     * This replaces get_status.php
     */
    public function index(AsteriskService $asterisk): JsonResponse
    {
        $callLogs = CallLog::all();
        $statuses = [];

        foreach ($callLogs as $log) {
            $statusClean = !empty($log->status) ? strtolower(trim($log->status)) : 'pending';
            if (!in_array($statusClean, ['pass', 'fail', 'route'])) {
                $statusClean = 'pending';
            }
            $statuses[$log->id] = $statusClean;
        }

        // Get active calls count
        $activeCalls = 0;
        $channelsRaw = $asterisk->execute("sudo /usr/sbin/asterisk -rx 'core show channels' 2>/dev/null");
        if ($channelsRaw && preg_match('/([0-9]+)\s+active\s+calls?/i', $channelsRaw, $m)) {
            $activeCalls = (int)$m[1];
        }

        $statuses['_active_calls'] = $activeCalls;

        return response()->json($statuses);
    }
}
