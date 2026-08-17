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
        $callLogs = CallLog::orderBy('id', 'desc')->get();
        $totalDids = $callLogs->count();

        // Get system stats
        $stats = $this->getSystemStats();

        return view('dashboard', [
            'callLogs' => $callLogs,
            'totalDids' => $totalDids,
            'stats' => $stats,
        ]);
    }

    /**
     * Provision a new DID
     */
    public function provision(Request $request)
    {
        $phoneNumber = preg_replace('/\s+/', '', $request->input('phone_number', ''));

        if (!empty($phoneNumber)) {
            CallLog::create([
                'phone_number' => $phoneNumber,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Update DID status to route
     */
    public function markAsRoute(CallLog $callLog)
    {
        $callLog->update(['status' => 'route']);

        return redirect()->route('dashboard');
    }

    /**
     * Reset DID status
     */
    public function resetStatus(CallLog $callLog)
    {
        $callLog->update([
            'status' => 'pending',
            'source_ip' => null,
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
     * Hangup all active calls
     */
    public function hangupAll()
    {
        $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");

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
            $ramPct = 45;
        } else if ($freeOutput && preg_match_all('/([0-9]+)/', $freeOutput, $matches)) {
            $totalRam = (int) ($matches[0][0] ?? 1024);
            $usedRam = (int) ($matches[0][1] ?? 0);
            $ramPct = ($totalRam > 0) ? (int) round(($usedRam / $totalRam) * 100) : 0;
        }

        return [
            'activeCalls' => $activeCalls,
            'onlinePeers' => $onlinePeers,
            'peerList' => $peerList,
            'ramUsage' => $ramPct,
            'uptime' => $this->getUptime(),
        ];
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
        $callLogs = CallLog::all();
        $stats = $this->getSystemStats();

        $response = [
            '_active_calls' => $stats['activeCalls'],
        ];

        // Add each DID's status and source IP
        foreach ($callLogs as $log) {
            $response[$log->id] = [
                'status' => $log->status ?: 'pending',
                'source_ip' => $log->source_ip ?: '—',
            ];
        }

        return response()->json($response);
    }
}
