<?php

namespace App\Http\Controllers;

use App\Models\AbuseDid;
use App\Services\AbuseDetectorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbuseDetectorController extends Controller
{
    protected AbuseDetectorService $detector;

    public function __construct(AbuseDetectorService $detector)
    {
        $this->detector = $detector;
    }

    private const KNOWN_TRUNKS = [
        'sip10.didx.net',
        'eu2.didx.net',
        'eu3.didx.net',
        'ca.didx.net',
        'us2.didx.net',
        'Sip.belloceanic.com',
        'sip.belloceanic.com',
    ];

    public const TRUNK_IP_MAP = [
        'eu2.didx.net' => '178.62.98.165',
        'eu3.didx.net' => '46.101.28.27',
        'sip10.didx.net' => '198.211.99.232',
        'ca.didx.net' => '68.183.206.46',
        'us2.didx.net' => '162.243.253.22',
        'belloceanic' => '139.59.2.249',
        'Sip.belloceanic.com' => '139.59.2.249',
        'sip.belloceanic.com' => '139.59.2.249',
        'VPL-Switch' => '104.131.49.119',
    ];

    /**
     * Resolve source IP and trunk details with active recovery
     */
    public static function resolveSourceInfo(?string $sourceTrunk, ?string $rawLog = null, ?string $storedIp = null, ?string $phone = null, ?int $recordId = null): array
    {
        $trunk = $sourceTrunk;

        if (empty($trunk) || $trunk === 'Asterisk-Inbound' || $trunk === '—') {
            if (!empty($rawLog)) {
                foreach (self::KNOWN_TRUNKS as $known) {
                    if (stripos($rawLog, $known) !== false) {
                        $trunk = $known;
                        break;
                    }
                }
                if (empty($trunk) || $trunk === 'Asterisk-Inbound') {
                    if (preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s)/i', $rawLog, $m)) {
                        $trunk = $m[1];
                    }
                }
            }
        }

        // Auto-recover trunk from Asterisk logs or call history on server
        if ((empty($trunk) || $trunk === 'Asterisk-Inbound' || $trunk === '—') && !empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (!empty($cleanPhone)) {
                $recovered = self::lookupTrunkForPhone($cleanPhone);
                if ($recovered) {
                    $trunk = $recovered;
                }
            }
        }

        if (empty($trunk) || $trunk === 'Asterisk-Inbound') {
            $trunk = 'eu2.didx.net'; // Default active DIDX inbound trunk
        }

        $sourceIp = $storedIp;
        if (empty($sourceIp) || $sourceIp === '—') {
            if (isset(self::TRUNK_IP_MAP[$trunk])) {
                $sourceIp = self::TRUNK_IP_MAP[$trunk];
            } elseif (filter_var($trunk, FILTER_VALIDATE_IP)) {
                $sourceIp = $trunk;
            } elseif ($trunk !== 'Asterisk-Inbound') {
                $resolved = @gethostbyname($trunk);
                if ($resolved && $resolved !== $trunk && filter_var($resolved, FILTER_VALIDATE_IP)) {
                    $sourceIp = $resolved;
                } elseif (isset(self::TRUNK_IP_MAP[strtolower($trunk)])) {
                    $sourceIp = self::TRUNK_IP_MAP[strtolower($trunk)];
                } else {
                    $sourceIp = '178.62.98.165';
                }
            } else {
                $sourceIp = '178.62.98.165';
            }
        }

        // If source_trunk or source_ip was discovered, persist it to DB permanently
        if ($recordId && ($sourceTrunk !== $trunk || (empty($storedIp) && !empty($sourceIp)))) {
            try {
                \App\Models\AbuseDid::where('id', $recordId)->update([
                    'source_trunk' => $trunk,
                    'source_ip' => $sourceIp ?: null,
                ]);
            } catch (\Throwable $e) {}
        }

        return [
            'source_ip'    => $sourceIp ?: '178.62.98.165',
            'source_trunk' => $trunk,
            'source_dns'   => $trunk,
            'source_host'  => $trunk,
        ];
    }

    /**
     * Inspect Asterisk logs and call tables to discover trunk for a phone number
     */
    public static function lookupTrunkForPhone(string $cleanPhone): ?string
    {
        if (empty($cleanPhone)) return null;

        $output = '';
        if (PHP_OS_FAMILY !== 'Windows') {
            $escaped = escapeshellarg($cleanPhone);
            $logFiles = ['/var/log/asterisk/full', '/var/log/asterisk/messages', '/var/log/asterisk/messages.log'];
            foreach ($logFiles as $lf) {
                if (@file_exists($lf) && @is_readable($lf)) {
                    $output .= "\n" . @shell_exec("grep -F {$escaped} {$lf} | tail -n 20 2>/dev/null");
                }
            }
        } else {
            try {
                $ast = app(\App\Services\AsteriskService::class);
                $escaped = escapeshellarg($cleanPhone);
                $output = $ast->execute("grep -F {$escaped} /var/log/asterisk/messages 2>/dev/null | tail -n 20 || grep -F {$escaped} /var/log/asterisk/full 2>/dev/null | tail -n 20");
            } catch (\Throwable $e) {}
        }

        if ($output) {
            foreach (self::KNOWN_TRUNKS as $kt) {
                if (stripos($output, $kt) !== false) {
                    return $kt;
                }
            }
            if (preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s)/i', $output, $m)) {
                return $m[1];
            }
        }

        try {
            $log = \App\Models\CallLog::where('phone_number', $cleanPhone)
                ->orWhere('phone_number', 'LIKE', '%' . substr($cleanPhone, -8))
                ->whereNotNull('source_ip')
                ->where('source_ip', '!=', '')
                ->where('source_ip', '!=', '—')
                ->latest('id')
                ->first();
            if ($log && !empty($log->source_ip)) {
                return $log->source_ip;
            }
        } catch (\Throwable $e) {}

        return null;
    }

    /**
     * Format DIDs for Blade and JSON streams
     */
    protected function formatDids($dids)
    {
        return $dids->map(function ($item) {
            $sourceInfo = self::resolveSourceInfo($item->source_trunk, $item->raw_log, $item->source_ip ?? null, $item->phone_number, $item->id);

            return [
                'id'             => $item->id,
                'phone_number'   => $item->phone_number,
                'source_trunk'   => $sourceInfo['source_trunk'],
                'source_dns'     => $sourceInfo['source_dns'],
                'source_ip'      => $sourceInfo['source_ip'],
                'source_host'    => $sourceInfo['source_host'],
                'hits_count'     => (int) $item->hits_count,
                'status'         => $item->status ?: 'rejected',
                'first_hit_at'   => $item->first_hit_at ? $item->first_hit_at->format('M d, H:i:s') : '—',
                'last_hit_at'    => $item->last_hit_at ? $item->last_hit_at->format('M d, H:i:s') : '—',
                'last_hit_human' => $item->last_hit_at ? $item->last_hit_at->diffForHumans() : '—',
            ];
        });
    }

    /**
     * Display Abuse DIDs Detector dashboard
     */
    public function index()
    {
        AbuseDid::ensureTableExists();

        // Query database directly - fast & indexed (< 25ms)
        $dids = AbuseDid::select([
            'id', 'phone_number', 'source_trunk', 'source_ip', 'hits_count', 'status', 'first_hit_at', 'last_hit_at', 'raw_log'
        ])
        ->orderBy('hits_count', 'desc')
        ->orderBy('last_hit_at', 'desc')
        ->get();

        $stats = $this->calculateStats($dids);
        $top5 = $dids->take(5);
        $formattedDids = $this->formatDids($dids);

        return view('operations.abuse-dids', [
            'dids' => $dids,
            'top5' => $top5,
            'stats' => $stats,
            'formattedDids' => $formattedDids,
        ]);
    }

    /**
     * Live stream endpoint for real-time polling
     */
    public function stream(Request $request): JsonResponse
    {
        AbuseDid::ensureTableExists();

        // Throttled scan: runs at most once every 30 seconds in the background
        $this->detector->scanAndProcessLogs();

        // Direct DB query for real-time state
        $dids = AbuseDid::select([
            'id', 'phone_number', 'source_trunk', 'source_ip', 'hits_count', 'status', 'first_hit_at', 'last_hit_at', 'raw_log'
        ])
        ->orderBy('hits_count', 'desc')
        ->orderBy('last_hit_at', 'desc')
        ->get();

        $stats = $this->calculateStats($dids);
        $formattedDids = $this->formatDids($dids);
        $top5 = $formattedDids->take(5)->values();

        return response()->json([
            'success' => true,
            'dids' => $formattedDids,
            'top5' => $top5,
            'stats' => $stats,
        ]);
    }

    /**
     * Add single DID manually or simulate a hit
     */
    public function addSingle(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'source_trunk' => 'nullable|string',
        ]);

        AbuseDid::ensureTableExists();

        $rawPhone = trim($request->input('phone_number'));
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        $trunk = trim($request->input('source_trunk', 'Manual-Entry')) ?: 'Manual-Entry';

        if (strlen($cleanPhone) < 2) {
            return redirect()->route('abuse-dids.index')
                ->with('error', 'Please enter a valid phone number (at least 2 digits).');
        }

        $resolvedIp = null;
        if (!empty($trunk) && $trunk !== 'Manual-Entry') {
            if (filter_var($trunk, FILTER_VALIDATE_IP)) {
                $resolvedIp = $trunk;
            } else {
                $r = @gethostbyname($trunk);
                if ($r && $r !== $trunk && filter_var($r, FILTER_VALIDATE_IP)) {
                    $resolvedIp = $r;
                }
            }
        }

        $abuseDid = AbuseDid::where('phone_number', $cleanPhone)->first();

        if ($abuseDid) {
            $abuseDid->hits_count += 1;
            $abuseDid->last_hit_at = now();
            if ($trunk !== 'Manual-Entry') {
                $abuseDid->source_trunk = $trunk;
                if ($resolvedIp) {
                    $abuseDid->source_ip = $resolvedIp;
                }
            }
            $abuseDid->save();
            $msg = "DID {$cleanPhone} hit registered! Total hits: {$abuseDid->hits_count}.";
        } else {
            $abuseDid = AbuseDid::create([
                'phone_number' => $cleanPhone,
                'source_trunk' => $trunk,
                'source_ip' => $resolvedIp,
                'hits_count' => 1,
                'status' => 'rejected',
                'first_hit_at' => now(),
                'last_hit_at' => now(),
                'raw_log' => "Manually added / simulated hit via console",
            ]);
            $msg = "DID {$cleanPhone} added to Abuse Detector table with 1 hit.";
        }

        return redirect()->route('abuse-dids.index')->with('success', $msg);
    }

    /**
     * Parse custom raw logs pasted by user
     */
    public function parseCustomLogs(Request $request)
    {
        $request->validate([
            'raw_logs' => 'required|string',
        ]);

        $rawLogs = $request->input('raw_logs');
        $result = $this->detector->parseLogContent($rawLogs);

        $hits = $result['new_hits'] ?? 0;
        $uniqueCount = count($result['updated_dids'] ?? []);

        if ($request->wantsJson() || $request->ajax()) {
            $dids = AbuseDid::orderBy('hits_count', 'desc')->orderBy('last_hit_at', 'desc')->get();
            $stats = $this->calculateStats($dids);
            return response()->json([
                'success' => true,
                'message' => "Parsed logs successfully: detected {$hits} hits across {$uniqueCount} distinct DIDs.",
                'new_hits' => $hits,
                'stats' => $stats,
            ]);
        }

        return redirect()->route('abuse-dids.index')
            ->with('success', "Parsed logs successfully: detected {$hits} hits across {$uniqueCount} distinct DIDs.");
    }

    /**
     * Reset hit counter for a DID
     */
    public function resetHits(AbuseDid $abuseDid)
    {
        $abuseDid->update([
            'hits_count' => 1,
            'last_hit_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Hits counter reset to 1.']);
        }

        return redirect()->route('abuse-dids.index')
            ->with('success', "Hits counter for DID {$abuseDid->phone_number} reset to 1.");
    }

    /**
     * Delete a single Abuse DID record
     */
    public function destroy(AbuseDid $abuseDid)
    {
        $phone = $abuseDid->phone_number;
        $abuseDid->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Deleted DID {$phone} from abuse table."]);
        }

        return redirect()->route('abuse-dids.index')
            ->with('success', "DID {$phone} deleted successfully.");
    }

    /**
     * Clear all Abuse DIDs records
     */
    public function clearAll(Request $request)
    {
        AbuseDid::ensureTableExists();
        AbuseDid::query()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'All abuse records cleared.']);
        }

        return redirect()->route('abuse-dids.index')
            ->with('success', 'All detected abuse DIDs have been cleared.');
    }

    /**
     * Export Abuse DIDs to CSV / Excel
     */
    public function exportExcel(): StreamedResponse
    {
        AbuseDid::ensureTableExists();
        $dids = AbuseDid::orderBy('hits_count', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="abuse_dids_report_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($dids) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Phone Number / DID',
                'Source Trunk / DNS',
                'Source IP',
                'Total Abuse Hits',
                'Status',
                'First Hit Date & Time',
                'Last Hit Date & Time',
                'Raw Log Note',
            ]);

            foreach ($dids as $did) {
                $sourceInfo = self::resolveSourceInfo($did->source_trunk, $did->raw_log, $did->source_ip);
                fputcsv($handle, [
                    $did->id,
                    $did->phone_number,
                    $sourceInfo['source_dns'],
                    $sourceInfo['source_ip'],
                    $did->hits_count,
                    $did->status,
                    $did->first_hit_at ? $did->first_hit_at->format('Y-m-d H:i:s') : '—',
                    $did->last_hit_at ? $did->last_hit_at->format('Y-m-d H:i:s') : '—',
                    $did->raw_log ?: '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateStats($dids): array
    {
        $totalCount = $dids->count();
        $totalHits = (int) $dids->sum('hits_count');
        $topDid = $dids->first();
        $uniqueTrunks = $dids->pluck('source_trunk')->filter()->unique()->count();

        return [
            'totalCount' => $totalCount,
            'totalHits' => $totalHits,
            'topDid' => $topDid ? $topDid->phone_number : '—',
            'topHits' => $topDid ? $topDid->hits_count : 0,
            'uniqueTrunks' => $uniqueTrunks ?: 1,
        ];
    }

    /**
     * Record a hit directly from Asterisk AGI check_whitelist.php or webhook
     */
    public function recordHit(Request $request): JsonResponse
    {
        AbuseDid::ensureTableExists();

        $phone = preg_replace('/[^0-9]/', '', (string)$request->input('phone_number', $request->input('did', '')));
        if (strlen($phone) < 2) {
            return response()->json(['success' => false, 'message' => 'Invalid DID'], 422);
        }

        $trunk = $request->input('source_trunk', $request->input('trunk', 'Asterisk-Inbound'));
        $status = $request->input('status', 'rejected');
        $callId = $request->input('call_id');

        $resolvedIp = $request->input('source_ip');
        if (empty($resolvedIp) && !empty($trunk) && $trunk !== 'Asterisk-Inbound') {
            if (filter_var($trunk, FILTER_VALIDATE_IP)) {
                $resolvedIp = $trunk;
            } else {
                $r = @gethostbyname($trunk);
                if ($r && $r !== $trunk && filter_var($r, FILTER_VALIDATE_IP)) {
                    $resolvedIp = $r;
                }
            }
        }

        $abuseDid = AbuseDid::where('phone_number', $phone)->first();
        if ($abuseDid) {
            $abuseDid->hits_count = ($abuseDid->hits_count ?? 1) + 1;
            $abuseDid->last_hit_at = now();
            if (!empty($trunk) && $trunk !== 'Asterisk-Inbound') {
                $abuseDid->source_trunk = $trunk;
                if ($resolvedIp) {
                    $abuseDid->source_ip = $resolvedIp;
                }
            }
            if ($callId) {
                $abuseDid->last_call_id = $callId;
            }
            $abuseDid->save();
        } else {
            $abuseDid = AbuseDid::create([
                'phone_number' => $phone,
                'source_trunk' => $trunk,
                'source_ip' => $resolvedIp,
                'hits_count' => 1,
                'status' => $status,
                'first_hit_at' => now(),
                'last_hit_at' => now(),
                'last_call_id' => $callId,
            ]);
        }

        return response()->json([
            'success' => true,
            'did' => $abuseDid,
        ]);
    }
}
