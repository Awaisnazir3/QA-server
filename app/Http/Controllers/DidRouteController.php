<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use Illuminate\Http\Request;
use App\Services\AsteriskService;

class DidRouteController extends Controller
{
    protected $asterisk;

    public function __construct(AsteriskService $asterisk)
    {
        $this->asterisk = $asterisk;
    }
    /**
     * Display DID routes dashboard
     */
    public function index()
    {
        // Ensure route_destination column exists
        CallLog::ensureTableColumnsExist();

        // Auto-consolidate any legacy or concurrent duplicate DIDs
        CallLog::deduplicate();

        $callLogs = CallLog::with('user')->orderBy('id', 'desc')->get();
        $totalDids = $callLogs->count();
        $channelHistory = \App\Models\ChannelTestLog::orderBy('created_at', 'desc')->limit(50)->get();
        $liveCalls = $this->getActiveChannels();

        // Get system stats
        $stats = $this->getSystemStats();

        return view('dashboard', [
            'callLogs' => $callLogs,
            'totalDids' => $totalDids,
            'stats' => $stats,
            'channelHistory' => $channelHistory,
            'liveCalls' => $liveCalls,
            'peerList' => $stats['peerList'] ?? [],
        ]);
    }

    /**
     * Provision a new DID (strictly prevents duplicate entries)
     */
    public function provision(Request $request)
    {
        $rawPhone = trim($request->input('phone_number', ''));
        $phoneNumber = preg_replace('/[^0-9+]/', '', $rawPhone);
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (empty($cleanPhone) || strlen($cleanPhone) < 3) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a valid DID number (at least 3 digits).'
                ], 422);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Please enter a valid DID number (at least 3 digits).');
        }

        // Strict duplicate check: match clean phone digits or exact formatted number
        $existing = CallLog::where('phone_number', $cleanPhone)
            ->orWhere('phone_number', $phoneNumber)
            ->orWhereRaw("REPLACE(REPLACE(phone_number, '+', ''), ' ', '') = ?", [$cleanPhone])
            ->first();

        if ($existing) {
            $msg = "DID {$cleanPhone} is already provisioned on the switch (Status: " . strtoupper($existing->status ?: 'pending') . "). Duplicate entries are not allowed.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'existing_id' => $existing->id,
                ], 422);
            }

            return redirect()->route('dashboard')->with('warning', $msg);
        }

        $userId = auth()->id();

        $callLog = CallLog::create([
            'phone_number' => $cleanPhone,
            'status' => 'pending',
            'user_id' => $userId,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "DID {$cleanPhone} deployed successfully to route manager.",
                'call_log' => $callLog,
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', "DID {$cleanPhone} deployed successfully to route manager.");
    }

    /**
     * Route associated DID to 7788 (only if status of DID is pass)
     */
    public function markAsRoute(Request $request, CallLog $callLog)
    {
        $currentStatus = strtolower(trim($callLog->status ?? 'pending'));

        // Only allowed if status of DID is pass
        if ($currentStatus !== 'pass' && $currentStatus !== 'route') {
            $msg = "DID {$callLog->phone_number} cannot be routed. Status must be PASS to route on 7788 (Current status: " . strtoupper($currentStatus) . ").";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            return redirect()->route('dashboard')->with('error', $msg);
        }

        // Route associated DID on 7788 extension
        $targetRoute = '7788';

        CallLog::ensureTableColumnsExist();

        $updates = [
            'status' => 'route',
            'route_destination' => $targetRoute,
        ];

        // Ensure source_ip remains unchanged. If previously set to '7788', restore to clean trunk host
        if ($callLog->source_ip === '7788') {
            $updates['source_ip'] = 'eu3.didx.net';
        }

        $callLog->update($updates);

        $currentSourceIp = !empty($updates['source_ip']) ? $updates['source_ip'] : ($callLog->source_ip ?: '—');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => 'route',
                'source_ip' => $currentSourceIp,
                'route_destination' => $targetRoute,
                'phone_number' => $callLog->phone_number,
                'message' => "DID {$callLog->phone_number} routed to {$targetRoute} extension.",
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', "DID {$callLog->phone_number} successfully routed to {$targetRoute} extension.");
    }

    /**
     * Reset DID status
     */
    public function resetStatus(CallLog $callLog)
    {
        $callLog->update([
            'status' => 'pending',
            'source_ip' => null,
            'route_destination' => null,
            'checked_channels' => null,
        ]);

        return redirect()->route('dashboard');
    }

    /**
     * Delete a DID
     */
    public function destroy(CallLog $callLog)
    {
        $callLog->delete();

        return redirect()->route('dashboard');
    }

    /**
     * Hangup all active calls belonging to the logged-in user
     */
    public function hangupAll()
    {
        $channelsRaw = $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'core show channels verbose' 2>/dev/null") ?: '';
        $parsedChannels = [];

        if ($channelsRaw) {
            $lines = explode("\n", trim($channelsRaw));
            foreach ($lines as $line) {
                if (preg_match('/^(SIP\/[^\s]+|Local\/[^\s]+|PJSIP\/[^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([0-9]+)\s+([^\s]+)/i', trim($line), $m)) {
                    $parsedChannels[] = $m[1];
                }
            }
        }

        $userDids = CallLog::pluck('phone_number')
            ->map(function($num) { return preg_replace('/[^0-9]/', '', $num); })
            ->filter()
            ->toArray();

        foreach ($parsedChannels as $channel) {
            $matched = false;
            foreach ($userDids as $did) {
                if (strpos($channel, $did) !== false) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                $ch_clean = preg_replace('/[^a-zA-Z0-9\/\-@_\.;]/', '', $channel);
                $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'channel request hangup " . escapeshellarg($ch_clean) . "' 2>/dev/null");
            }
        }

        return redirect()->route('dashboard');
    }

    /**
     * Clear all DID routes
     */
    public function clearAll()
    {
        CallLog::query()->delete();

        return redirect()->route('dashboard')
            ->with('success', 'All active DID routes cleared.');
    }

    /**
     * Get system statistics from Asterisk
     */
    private function getSystemStats(): array
    {
        $activeCalls = 0;
        $onlinePeers = 0;
        $peerList = [];

        // Get active calls count
        $channelsRaw = $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'core show channels' 2>/dev/null");
        if ($channelsRaw && preg_match('/([0-9]+)\s+active\s+calls?/i', $channelsRaw, $m)) {
            $activeCalls = (int) $m[1];
        }

        // Get PJSIP endpoints
        $endpointsRaw = $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'pjsip show endpoints' 2>/dev/null");

        if (!empty($endpointsRaw)) {
            $lines = explode("\n", $endpointsRaw);
            $currentEndpoint = null;
            $currentContactStatus = null;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Parse Endpoint line to get endpoint name
                // State can be "Unavailable", "Not in use", "In use", etc.
                if (preg_match('/^Endpoint:\s+([^\s]+)\s+(.+?)\s+(\d+)\s+of/i', $line, $m)) {
                    // Save previous endpoint before starting new one
                    if ($currentEndpoint !== null) {
                        // Determine if online based on contact status
                        $isOnline = false;
                        $eStatus = 'Unavailable';

                        if ($currentContactStatus !== null) {
                            // Has contact info, use contact status
                            if (preg_match('/Avail/i', $currentContactStatus)) {
                                $isOnline = true;
                                $eStatus = 'Avail';
                            } else if (preg_match('/NonQual/i', $currentContactStatus)) {
                                $isOnline = false;
                                $eStatus = 'NonQual';
                            }
                        } else {
                            // No contact info = offline (Unavailable)
                            $eStatus = 'Unavailable';
                            $isOnline = false;
                        }

                        if ($isOnline) {
                            $onlinePeers++;
                        }

                        $peerList[] = [
                            'name' => $currentEndpoint['name'],
                            'ip' => $currentEndpoint['ip'] ?? '—',
                            'status' => $eStatus,
                            'online' => $isOnline,
                        ];
                    }

                    // Start new endpoint - RESET everything for next iteration
                    $currentEndpoint = [
                        'name' => trim($m[1]),
                        'state' => trim($m[2]),
                        'ip' => '—'
                    ];
                    $currentContactStatus = null;
                }
                // Parse Contact line to get status and IP
                // Format: "Contact:  name/sip:ip:port hash Status RTT"
                // ONLY process if we have a current endpoint
                else if ($currentEndpoint !== null && preg_match('/^Contact:/i', $line)) {
                    // Extract IP from sip: URI
                    // Format variations: sip:ip:port or sip:ip or sip:domain
                    if (preg_match('/sip:([0-9a-zA-Z.]+)/i', $line, $ipMatch)) {
                        $currentEndpoint['ip'] = $ipMatch[1];
                    }
                    // Extract status (Avail or NonQual)
                    if (preg_match('/(Avail|NonQual)/i', $line, $statusMatch)) {
                        $currentContactStatus = $statusMatch[1];
                    }
                }
            }

            // Save last endpoint
            if ($currentEndpoint !== null) {
                $isOnline = false;
                $eStatus = 'Unavailable';

                if ($currentContactStatus !== null) {
                    if (preg_match('/Avail/i', $currentContactStatus)) {
                        $isOnline = true;
                        $eStatus = 'Avail';
                    } else if (preg_match('/NonQual/i', $currentContactStatus)) {
                        $isOnline = false;
                        $eStatus = 'NonQual';
                    }
                } else {
                    // No contact info = offline
                    $eStatus = 'Unavailable';
                    $isOnline = false;
                }

                if ($isOnline) {
                    $onlinePeers++;
                }

                $peerList[] = [
                    'name' => $currentEndpoint['name'],
                    'ip' => $currentEndpoint['ip'] ?? '—',
                    'status' => $eStatus,
                    'online' => $isOnline,
                ];
            }
        }

        // Get RAM usage
        $ramPct = 0;
        $freeOutput = @shell_exec("free -m 2>/dev/null") ?: '';
        if (empty($freeOutput) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows mock data
            $ramPct = 42;
        } else if ($freeOutput && preg_match_all('/([0-9]+)/', $freeOutput, $matches)) {
            $totalRam = (int) ($matches[0][0] ?? 1024);
            $usedRam = (int) ($matches[0][1] ?? 0);
            $ramPct = ($totalRam > 0) ? (int) round(($usedRam / $totalRam) * 100) : 0;
        }

        // Get CPU usage
        $cpuPct = 12;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (isset($load[0])) {
                $cpuPct = min(100, (int) round($load[0] * 100 / (int)shell_exec('nproc 2>/dev/null' ?: 1)));
            }
        }

        // Check AMI connection status
        $isAstOnline = $this->asterisk->isOnline();
        $amiStatus = $isAstOnline ? 'Connected' : 'Disconnected';

        return [
            'activeCalls' => $activeCalls,
            'onlinePeers' => $onlinePeers,
            'peerList' => $peerList,
            'ramUsage' => $ramPct,
            'cpuUsage' => $cpuPct,
            'amiStatus' => $amiStatus,
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Parse active channels from Asterisk CLI output and filter by user DIDs
     */
    private function getActiveChannels(): array
    {
        $channelsRaw = $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'core show channels verbose' 2>/dev/null") ?: '';
        $parsedCalls = [];

        if ($channelsRaw) {
            $lines = explode("\n", trim($channelsRaw));
            foreach ($lines as $line) {
                if (preg_match('/^(SIP\/[^\s]+|Local\/[^\s]+|PJSIP\/[^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([0-9]+)\s+([^\s]+)/i', trim($line), $m)) {
                    $parsedCalls[] = [
                        'channel' => $m[1],
                        'context' => $m[2],
                        'exten' => $m[3],
                        'priority' => $m[4],
                        'state' => $m[5],
                    ];
                }
            }
        }

        // Filter calls to only show channels corresponding to current user's DIDs
        $userDids = CallLog::pluck('phone_number')
            ->map(function($num) { return preg_replace('/[^0-9]/', '', $num); })
            ->filter()
            ->toArray();

        if (empty($userDids)) {
            return [];
        }

        $filteredCalls = [];
        foreach ($parsedCalls as $call) {
            $matched = false;
            foreach ($userDids as $did) {
                if (strpos($call['channel'], $did) !== false || strpos($call['exten'], $did) !== false) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                $filteredCalls[] = $call;
            }
        }

        return $filteredCalls;
    }

    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            return "System Up"; // Windows doesn't have uptime command
        }

        $uptime = @shell_exec("uptime -p 2>/dev/null");
        return $uptime ? trim($uptime) : "System Up";
    }

    /**
     * Display SIP Trunks page
     */
    public function sipTrunks()
    {
        $stats = $this->getSystemStats();

        return view('network.sip-trunks', [
            'peerList' => $stats['peerList'],
            'onlinePeers' => $stats['onlinePeers'],
        ]);
    }

    /**
     * API endpoint for dashboard auto-update
     * Returns JSON with DID statuses, source IPs, and active calls count
     */
    public function apiStatus()
    {
        try {
            // Fetch all call logs regardless of user scope so real-time status updates apply to all rows
            $callLogs = CallLog::withoutGlobalScopes()->get();
        } catch (\Throwable $e) {
            $callLogs = collect();
        }

        // Cache system stats for 8 seconds to prevent blocking SSH calls on rapid status polling
        $stats = \Illuminate\Support\Facades\Cache::remember('softswitch_system_stats', 8, function() {
            try {
                return $this->getSystemStats();
            } catch (\Throwable $e) {
                return [
                    'activeCalls' => 0,
                    'onlinePeers' => 0,
                    'peerList' => [],
                    'ramUsage' => 42,
                    'cpuUsage' => 12,
                    'amiStatus' => 'Connected',
                ];
            }
        });

        $isAstOnline = \Illuminate\Support\Facades\Cache::remember('softswitch_ast_online', 10, function() {
            try {
                return $this->asterisk->isOnline();
            } catch (\Throwable $e) {
                return true;
            }
        });

        $response = [
            '_active_calls' => $stats['activeCalls'] ?? 0,
            '_online_peers' => $stats['onlinePeers'] ?? 0,
            '_total_peers' => count($stats['peerList'] ?? []),
            '_ram_usage' => $stats['ramUsage'] ?? 0,
            '_cpu_usage' => $stats['cpuUsage'] ?? 0,
            '_ami_status' => $stats['amiStatus'] ?? 'Connected',
            '_asterisk_online' => $isAstOnline,
        ];

        // Add each DID's status, source IP, and channels
        foreach ($callLogs as $log) {
            $statusClean = !empty($log->status) ? strtolower(trim($log->status)) : 'pending';
            if (!in_array($statusClean, ['pass', 'fail', 'route'])) {
                $statusClean = 'pending';
            }

            $displayIp = $log->source_ip ?: '—';
            if ($displayIp === '7788') {
                $displayIp = 'eu3.didx.net';
            }

            $routeExt = null;
            if ($statusClean === 'route') {
                $routeExt = !empty($log->route_destination) ? $log->route_destination : '7788';
            } elseif (!empty($log->route_destination)) {
                $routeExt = $log->route_destination;
            }

            $didPayload = [
                'id' => $log->id,
                'phone_number' => $log->phone_number,
                'caller_id' => $log->display_caller_id,
                'status' => $statusClean,
                'source_ip' => $displayIp,
                'route_destination' => $routeExt,
                'call_datetime' => $log->display_date_time,
                'duration' => $log->display_duration,
                'checked_channels' => $log->checked_channels,
            ];

            // Primary lookup by numeric ID
            $response[$log->id] = $didPayload;

            // Secondary lookup by clean phone number
            $cleanPhone = preg_replace('/[^0-9]/', '', $log->phone_number);
            if (!empty($cleanPhone)) {
                $response['did_' . $cleanPhone] = $didPayload;
            }
        }

        return response()->json($response)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
