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

    /**
     * Display Abuse DIDs Detector dashboard
     */
    public function index()
    {
        AbuseDid::ensureTableExists();

        // Query database directly - fast & indexed (< 25ms)
        $dids = AbuseDid::select([
            'id', 'phone_number', 'source_trunk', 'hits_count', 'status', 'first_hit_at', 'last_hit_at'
        ])
        ->orderBy('hits_count', 'desc')
        ->orderBy('last_hit_at', 'desc')
        ->get();

        $stats = $this->calculateStats($dids);
        $top5 = $dids->take(5);

        // Pre-format DIDs for fast JSON hydration in JavaScript (avoids 859 Blade diffForHumans loops)
        $formattedDids = $dids->map(function ($item) {
            return [
                'id' => $item->id,
                'phone_number' => $item->phone_number,
                'source_trunk' => $item->source_trunk ?: 'Asterisk-Inbound',
                'hits_count' => (int) $item->hits_count,
                'status' => $item->status ?: 'rejected',
                'first_hit_at' => $item->first_hit_at ? $item->first_hit_at->format('M d, H:i:s') : '—',
                'last_hit_at' => $item->last_hit_at ? $item->last_hit_at->format('M d, H:i:s') : '—',
                'last_hit_human' => $item->last_hit_at ? $item->last_hit_at->diffForHumans() : '—',
            ];
        });

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
            'id', 'phone_number', 'source_trunk', 'hits_count', 'status', 'first_hit_at', 'last_hit_at'
        ])
        ->orderBy('hits_count', 'desc')
        ->orderBy('last_hit_at', 'desc')
        ->get();

        $stats = $this->calculateStats($dids);

        $formattedDids = $dids->map(function ($item) {
            return [
                'id' => $item->id,
                'phone_number' => $item->phone_number,
                'source_trunk' => $item->source_trunk ?: 'Asterisk-Inbound',
                'hits_count' => (int) $item->hits_count,
                'status' => $item->status ?: 'rejected',
                'first_hit_at' => $item->first_hit_at ? $item->first_hit_at->format('M d, H:i:s') : '—',
                'last_hit_at' => $item->last_hit_at ? $item->last_hit_at->format('M d, H:i:s') : '—',
                'last_hit_human' => $item->last_hit_at ? $item->last_hit_at->diffForHumans() : '—',
            ];
        });

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

        $abuseDid = AbuseDid::where('phone_number', $cleanPhone)->first();

        if ($abuseDid) {
            $abuseDid->hits_count += 1;
            $abuseDid->last_hit_at = now();
            if ($trunk !== 'Manual-Entry') {
                $abuseDid->source_trunk = $trunk;
            }
            $abuseDid->save();
            $msg = "DID {$cleanPhone} hit registered! Total hits: {$abuseDid->hits_count}.";
        } else {
            $abuseDid = AbuseDid::create([
                'phone_number' => $cleanPhone,
                'source_trunk' => $trunk,
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
                'Source Trunk / Peer',
                'Total Abuse Hits',
                'Status',
                'First Hit Date & Time',
                'Last Hit Date & Time',
                'Raw Log Note',
            ]);

            foreach ($dids as $did) {
                fputcsv($handle, [
                    $did->id,
                    $did->phone_number,
                    $did->source_trunk ?: '—',
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
        if (strlen($phone) < 4) {
            return response()->json(['success' => false, 'message' => 'Invalid DID'], 422);
        }

        $trunk = $request->input('source_trunk', $request->input('trunk', 'Asterisk-Inbound'));
        $status = $request->input('status', 'rejected');
        $callId = $request->input('call_id');

        $abuseDid = AbuseDid::where('phone_number', $phone)->first();
        if ($abuseDid) {
            $abuseDid->hits_count = ($abuseDid->hits_count ?? 1) + 1;
            $abuseDid->last_hit_at = now();
            if (!empty($trunk) && $trunk !== 'Asterisk-Inbound') {
                $abuseDid->source_trunk = $trunk;
            }
            if ($callId) {
                $abuseDid->last_call_id = $callId;
            }
            $abuseDid->save();
        } else {
            $abuseDid = AbuseDid::create([
                'phone_number' => $phone,
                'source_trunk' => $trunk,
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
