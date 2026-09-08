<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbuseDid;
use App\Models\CallLog;
use App\Models\BulkDid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbuseDidApiController extends Controller
{
    /**
     * Preconfigured PJSIP Source IP / DNS trunks
     */
    private const KNOWN_TRUNKS = [
        'sip10.didx.net',
        'eu2.didx.net',
        'eu3.didx.net',
        'ca.didx.net',
        'us2.didx.net',
        'Sip.belloceanic.com',
        'sip.belloceanic.com',
    ];

    /**
     * Clean phone number by removing non-numeric characters and leading zeroes
     */
    private function cleanPhone(string $number): string
    {
        $digits = preg_replace('/[^0-9]/', '', $number);
        return ltrim($digits, '0');
    }

    /**
     * Find an AbuseDid record by raw or cleaned phone number
     */
    private function findAbuseDid(string $rawPhone): ?AbuseDid
    {
        AbuseDid::ensureTableExists();

        $rawPhone = trim($rawPhone);
        if (empty($rawPhone)) {
            return null;
        }

        // 1. Exact match query
        $record = AbuseDid::where('phone_number', $rawPhone)
            ->orWhere('phone_number', '+' . ltrim($rawPhone, '+'))
            ->orWhere('phone_number', ltrim($rawPhone, '+'))
            ->latest('id')
            ->first();

        if ($record) {
            return $record;
        }

        // 2. Clean digits comparison
        $cleanQuery = $this->cleanPhone($rawPhone);
        if (empty($cleanQuery)) {
            return null;
        }

        // Search records whose cleaned digits match or end with clean query
        $candidates = AbuseDid::where('phone_number', 'LIKE', '%' . substr($cleanQuery, -7))
            ->latest('id')
            ->get();

        foreach ($candidates as $cand) {
            if ($this->cleanPhone($cand->phone_number) === $cleanQuery) {
                return $cand;
            }
        }

        // 3. Fallback scan if needed
        return AbuseDid::all()->first(function ($cand) use ($cleanQuery) {
            return $this->cleanPhone($cand->phone_number) === $cleanQuery;
        });
    }

    /**
     * Inspect Asterisk log files and related database tables to recover the trunk/IP for this DID
     */
    private function lookupTrunkFromHistory(string $cleanPhone): ?string
    {
        if (empty($cleanPhone)) {
            return null;
        }

        // 1. Search Asterisk log files on Linux production server
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if (!$isWindows) {
            $escapedPhone = escapeshellarg($cleanPhone);
            $logFiles = [
                '/var/log/asterisk/full',
                '/var/log/asterisk/messages',
                '/var/log/asterisk/messages.log',
                '/var/log/asterisk/debug',
            ];

            foreach ($logFiles as $lf) {
                if (@file_exists($lf) && @is_readable($lf)) {
                    $cmd = "grep -F {$escapedPhone} {$lf} | tail -n 20 2>/dev/null";
                    $output = @shell_exec($cmd);
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
                }
            }
        }

        // 2. Check call_logs table
        try {
            $log = CallLog::where('phone_number', $cleanPhone)
                ->orWhere('phone_number', '+' . $cleanPhone)
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

        // 3. Check bulk_dids table
        try {
            $bulk = BulkDid::where('phone_number', $cleanPhone)
                ->orWhere('phone_number', '+' . $cleanPhone)
                ->orWhere('phone_number', 'LIKE', '%' . substr($cleanPhone, -8))
                ->whereNotNull('source_ip')
                ->where('source_ip', '!=', '')
                ->where('source_ip', '!=', '—')
                ->latest('id')
                ->first();

            if ($bulk && !empty($bulk->source_ip)) {
                return $bulk->source_ip;
            }
        } catch (\Throwable $e) {}

        return null;
    }

    /**
     * Resolve source IP and trunk/host details
     */
    private function resolveSourceInfo(?string $sourceTrunk, ?string $rawLog = null, ?string $phone = null, ?AbuseDid $abuseDid = null): array
    {
        $trunk = $sourceTrunk;

        // If source_trunk is missing or generic "Asterisk-Inbound", inspect raw_log first
        if (empty($trunk) || $trunk === 'Asterisk-Inbound') {
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

        // If still unresolved, search Asterisk logs and related tables for this phone number
        if ((empty($trunk) || $trunk === 'Asterisk-Inbound') && !empty($phone)) {
            $cleanPhone = $this->cleanPhone($phone);
            $recoveredTrunk = $this->lookupTrunkFromHistory($cleanPhone);
            if ($recoveredTrunk) {
                $trunk = $recoveredTrunk;
                // Persist recovered trunk in abuse_dids table
                if ($abuseDid) {
                    try {
                        $abuseDid->update(['source_trunk' => $recoveredTrunk]);
                    } catch (\Throwable $e) {}
                }
            }
        }

        if (empty($trunk)) {
            $trunk = 'Asterisk-Inbound';
        }

        $sourceIp = null;

        // Check if trunk is already a valid IP address
        if (filter_var($trunk, FILTER_VALIDATE_IP)) {
            $sourceIp = $trunk;
        } elseif ($trunk !== 'Asterisk-Inbound') {
            // Attempt DNS resolution to obtain IP
            $resolved = @gethostbyname($trunk);
            if ($resolved && $resolved !== $trunk && filter_var($resolved, FILTER_VALIDATE_IP)) {
                $sourceIp = $resolved;
            } else {
                $sourceIp = $trunk; // Return hostname if IP resolution unavailable
            }
        } else {
            // Fallback: check raw log for any IP
            if (!empty($rawLog) && preg_match('/\b([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\b/', $rawLog, $ipM)) {
                $sourceIp = $ipM[1];
            }
        }

        return [
            'source_ip'    => $sourceIp ?: ($trunk !== 'Asterisk-Inbound' ? $trunk : null),
            'source_trunk' => $trunk,
            'source_host'  => $trunk !== 'Asterisk-Inbound' ? $trunk : null,
        ];
    }

    /**
     * Format AbuseDid model into API response array
     * If exists: status = "PASS"
     * If not exists: status = "Not-Available"
     */
    private function formatResponse(string $requestedDid, ?AbuseDid $abuseDid): array
    {
        if (!$abuseDid) {
            return [
                'did'           => $requestedDid,
                'requested_did' => $requestedDid,
                'found'         => false,
                'status'        => 'Not-Available',
                'hits_count'    => 0,
                'source_ip'     => null,
                'source_trunk'  => null,
                'source_host'   => null,
                'first_hit_at'  => null,
                'last_hit_at'   => null,
            ];
        }

        $sourceInfo = $this->resolveSourceInfo(
            $abuseDid->source_trunk,
            $abuseDid->raw_log,
            $abuseDid->phone_number,
            $abuseDid
        );

        return [
            'did'           => $abuseDid->phone_number,
            'requested_did' => $requestedDid,
            'found'         => true,
            'status'        => 'PASS',
            'hits_count'    => (int) $abuseDid->hits_count,
            'source_ip'     => $sourceInfo['source_ip'],
            'source_trunk'  => $sourceInfo['source_trunk'],
            'source_host'   => $sourceInfo['source_host'],
            'first_hit_at'  => $abuseDid->first_hit_at ? $abuseDid->first_hit_at->toDateTimeString() : null,
            'last_hit_at'   => $abuseDid->last_hit_at ? $abuseDid->last_hit_at->toDateTimeString() : null,
            'created_at'    => $abuseDid->created_at ? $abuseDid->created_at->toDateTimeString() : null,
            'updated_at'    => $abuseDid->updated_at ? $abuseDid->updated_at->toDateTimeString() : null,
        ];
    }

    /**
     * Check if DID exists in Abuse DIDs list
     * 
     * Supports:
     * - GET /api/abuse-did/check?did=1234567890
     * - GET /api/abuse-did/check?phone_number=1234567890
     * - POST /api/abuse-did/check {"did": "1234567890"}
     * - POST /api/abuse-did/check {"dids": ["1234567890", "0987654321"]}
     */
    public function check(Request $request): JsonResponse
    {
        // Check if multiple DIDs are submitted as an array
        $dids = $request->input('dids');
        if (is_array($dids) && count($dids) > 0) {
            return $this->batchCheck($request);
        }

        $did = $request->input('did') ?? $request->input('phone_number') ?? $request->query('did') ?? $request->query('phone_number');

        if (empty($did)) {
            return response()->json([
                'success' => false,
                'message' => 'The "did" or "phone_number" parameter is required.',
            ], 422);
        }

        $record = $this->findAbuseDid((string)$did);
        $result = $this->formatResponse((string)$did, $record);

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Check status of an Abuse DID via route parameter
     * 
     * Example: GET /api/abuse-did/status/{did}
     */
    public function getStatusByParam(string $did): JsonResponse
    {
        if (empty(trim($did))) {
            return response()->json([
                'success' => false,
                'message' => 'DID parameter is required.',
            ], 422);
        }

        $record = $this->findAbuseDid($did);
        $result = $this->formatResponse($did, $record);

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Batch check multiple DIDs against Abuse DIDs list
     * 
     * Example: POST /api/abuse-did/batch-check {"dids": ["1234567890", "0987654321"]}
     */
    public function batchCheck(Request $request): JsonResponse
    {
        $dids = $request->input('dids');

        if (!is_array($dids) || empty($dids)) {
            return response()->json([
                'success' => false,
                'message' => 'The "dids" parameter must be a non-empty array of phone numbers.',
            ], 422);
        }

        $results = [];
        foreach ($dids as $did) {
            $didStr = (string)$did;
            $record = $this->findAbuseDid($didStr);
            $results[] = $this->formatResponse($didStr, $record);
        }

        return response()->json([
            'success' => true,
            'total'   => count($results),
            'results' => $results,
        ]);
    }

    /**
     * List all recorded Abuse DIDs
     * 
     * Example: GET /api/abuse-did/list
     */
    public function listAll(Request $request): JsonResponse
    {
        AbuseDid::ensureTableExists();

        $query = AbuseDid::orderBy('hits_count', 'desc')->orderBy('last_hit_at', 'desc');

        $limit = $request->query('limit', 100);
        $records = $query->limit((int)$limit)->get()->map(function ($did) {
            $sourceInfo = $this->resolveSourceInfo($did->source_trunk, $did->raw_log, $did->phone_number, $did);

            return [
                'id'            => $did->id,
                'phone_number'  => $did->phone_number,
                'status'        => 'PASS',
                'hits_count'    => (int) $did->hits_count,
                'source_ip'     => $sourceInfo['source_ip'],
                'source_trunk'  => $sourceInfo['source_trunk'],
                'source_host'   => $sourceInfo['source_host'],
                'first_hit_at'  => $did->first_hit_at ? $did->first_hit_at->toDateTimeString() : null,
                'last_hit_at'   => $did->last_hit_at ? $did->last_hit_at->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'total'   => $records->count(),
            'data'    => $records,
        ]);
    }
}
