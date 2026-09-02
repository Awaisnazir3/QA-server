<?php

namespace App\Http\Controllers;

use App\Models\BulkDid;
use App\Models\CallLog;
use App\Services\AsteriskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkTestController extends Controller
{
    /**
     * Display Bulk DID testing interface
     */
    public function index()
    {
        $dids = BulkDid::orderBy('id', 'desc')->get();
        $totalCount = $dids->count();
        $passCount = $dids->where('status', 'pass')->count();
        $failCount = $dids->where('status', 'fail')->count();
        $pendingCount = $dids->whereIn('status', ['pending', 'dialing'])->count();

        return view('operations.bulk-test', [
            'dids' => $dids,
            'bulkDids' => $dids,
            'totalCount' => $totalCount,
            'passCount' => $passCount,
            'failCount' => $failCount,
            'pendingCount' => $pendingCount,
        ]);
    }

    /**
     * Strip non-digit characters for phone comparison
     */
    private function cleanPhone(string $number): string
    {
        $digits = preg_replace('/[^0-9]/', '', $number);
        return ltrim($digits, '0');
    }

    /**
     * Add a single DID manually to Bulk Test list
     */
    public function addSingle(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $rawPhone = trim($request->input('phone_number'));
        $phoneNumber = preg_replace('/[^0-9+]/', '', $rawPhone);
        $cleanPhone = $this->cleanPhone($phoneNumber);

        if (empty($cleanPhone) || strlen($cleanPhone) < 3) {
            return redirect()->route('bulk-test.index')
                ->with('error', 'Please enter a valid phone number (at least 3 digits).');
        }

        // Check if DID already exists in Bulk Test list
        $existing = BulkDid::all()->filter(function ($item) use ($cleanPhone) {
            return $this->cleanPhone($item->phone_number) === $cleanPhone;
        });

        if ($existing->isNotEmpty()) {
            return redirect()->route('bulk-test.index')
                ->with('warning', "DID {$phoneNumber} is already in the Bulk Test list.")
                ->with('skipped_dids', [['phone' => $phoneNumber, 'reason' => 'Already in Bulk Test list']]);
        }

        BulkDid::create([
            'phone_number' => $phoneNumber,
            'source_ip' => null,
            'status' => 'pending',
            'last_tested_at' => null,
        ]);

        return redirect()->route('bulk-test.index')
            ->with('success', "DID {$phoneNumber} added to Bulk Test list.");
    }

    /**
     * Upload and parse file containing DIDs
     */
    public function upload(Request $request)
    {
        $request->validate([
            'did_file' => 'nullable|file|mimes:csv,txt,xlsx,xls,text|max:10240',
        ]);

        if (!$request->hasFile('did_file') || !$request->file('did_file')->isValid()) {
            return redirect()->route('bulk-test.index')
                ->with('error', 'Please select a valid CSV or TXT file to upload.');
        }

        $content = file_get_contents($request->file('did_file')->getRealPath());

        if (empty(trim($content))) {
            return redirect()->route('bulk-test.index')
                ->with('error', 'The uploaded file is empty.');
        }

        // Get list of existing DIDs in Bulk Test tab only
        $existingBulkCleanPhones = BulkDid::pluck('phone_number')
            ->map(function ($num) { return $this->cleanPhone($num); })
            ->filter()->toArray();

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $addedCount = 0;
        $skippedDids = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }

            $parts = preg_split('/[,\|\t]+/', $line);
            $rawPhone = trim($parts[0] ?? '');

            $phoneNumber = preg_replace('/[^0-9+]/', '', $rawPhone);
            $cleanPhone = $this->cleanPhone($phoneNumber);

            if (empty($cleanPhone) || strlen($cleanPhone) < 3) {
                continue;
            }

            if (in_array($cleanPhone, $existingBulkCleanPhones)) {
                $skippedDids[] = ['phone' => $phoneNumber, 'reason' => 'Already in Bulk Test list'];
                continue;
            }

            BulkDid::create([
                'phone_number' => $phoneNumber,
                'source_ip' => null,
                'status' => 'pending',
                'last_tested_at' => null,
            ]);

            $existingBulkCleanPhones[] = $cleanPhone;
            $addedCount++;
        }

        $msg = "Imported {$addedCount} DIDs into Bulk Test list.";
        if (count($skippedDids) > 0) {
            $msg .= " " . count($skippedDids) . " DIDs already exist and were skipped.";
        }

        return redirect()->route('bulk-test.index')
            ->with($addedCount > 0 ? 'success' : 'warning', $msg)
            ->with('skipped_dids', $skippedDids);
    }

    /**
     * Dial a single bulk DID and update status directly in bulk_dids table
     */
    public function dialSingle(BulkDid $bulkDid, AsteriskService $asterisk): JsonResponse
    {
        $bulkDid->update(['status' => 'dialing']);

        $phoneNumber = preg_replace('/[^0-9+]/', '', $bulkDid->phone_number);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $useSsh = $isWindows && env('ASTERISK_SSH_HOST');
        $status = 'pass';
        $sourceIp = null;

        if ($isWindows && !$useSsh) {
            // Simulation on dev/Windows — always returns pass with a random trunk IP
            $sampleIps = ['198.211.99.232', '162.243.253.22', '178.62.98.165', '104.131.49.119', '139.59.2.249', '68.183.206.46'];
            $sourceIp = $sampleIps[array_rand($sampleIps)];
            $status = 'pass';
        } else {
            // Linux or SSH — execute Asterisk originate and capture result
            $cmd = "sudo /usr/sbin/asterisk -rx "
                 . "'originate Local/{$phoneNumber}@outbound7788/n"
                 . " application WaitExten 5"
                 . " callerid \"BulkTest <{$phoneNumber}>\""
                 . "' 2>&1";

            $output = $asterisk->execute($cmd) ?: '';

            if (strpos(strtolower($output), 'fail') !== false || strpos(strtolower($output), 'error') !== false) {
                $status = 'fail';
            } else {
                $status = 'pass';
            }

            // Try to detect source IP from Asterisk endpoint info
            $endpointsRaw = $asterisk->execute("sudo /usr/sbin/asterisk -rx 'pjsip show endpoints' 2>/dev/null") ?: '';
            if (preg_match('/sip:([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/i', $endpointsRaw, $ipMatch)) {
                $sourceIp = $ipMatch[1];
            }
        }

        // Update bulk_dids table
        $bulkDid->update([
            'status'        => $status,
            'source_ip'     => $sourceIp,
            'last_tested_at' => now(),
        ]);

        // Sync status to matching call_logs row (matched by phone_number)
        $cleanPhone = preg_replace('/[^0-9]/', '', $bulkDid->phone_number);
        $matchingLog = CallLog::all()->first(function ($log) use ($cleanPhone) {
            return preg_replace('/[^0-9]/', '', $log->phone_number) === $cleanPhone;
        });
        if ($matchingLog) {
            $matchingLog->update([
                'status'    => $status,
                'source_ip' => $sourceIp,
            ]);
        }

        $bulkDid->refresh();

        return response()->json([
            'success' => true,
            'did' => [
                'id' => $bulkDid->id,
                'phone_number' => $bulkDid->phone_number,
                'source_ip' => $bulkDid->source_ip ?: '—',
                'status' => $bulkDid->status,
            ],
        ]);
    }

    /**
     * API Status Endpoint — reads ONLY from bulk_dids
     */
    public function apiStatus(): JsonResponse
    {
        $dids = BulkDid::all();
        $response = [];

        foreach ($dids as $did) {
            $response[$did->id] = [
                'status' => strtolower($did->status ?: 'pending'),
                'source_ip' => $did->source_ip ?: '—',
            ];
        }

        return response()->json($response);
    }

    /**
     * Reset a single DID to PENDING
     */
    public function reset(Request $request, BulkDid $bulkDid)
    {
        $bulkDid->update([
            'status' => 'pending',
            'source_ip' => null,
            'last_tested_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'did' => [
                'id' => $bulkDid->id,
                'phone_number' => $bulkDid->phone_number,
                'source_ip' => '—',
                'status' => 'pending',
            ]
        ]);
    }

    /**
     * Reset ALL bulk DIDs to PENDING
     */
    public function resetAll()
    {
        BulkDid::query()->update([
            'status' => 'pending',
            'source_ip' => null,
            'last_tested_at' => null,
        ]);

        return redirect()->route('bulk-test.index')
            ->with('success', 'All bulk DIDs reset to pending.');
    }

    /**
     * Delete a single bulk DID
     */
    public function destroy(Request $request, BulkDid $bulkDid)
    {
        $bulkDid->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Clear ALL bulk DIDs
     */
    public function clearAll()
    {
        BulkDid::query()->delete();

        return redirect()->route('bulk-test.index')
            ->with('success', 'All bulk DIDs cleared.');
    }

    /**
     * Export bulk DID test results as Excel-compatible CSV
     */
    public function exportExcel(): StreamedResponse
    {
        $filename = 'Bulk_DID_Test_Report_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($file, ['Serial No', 'DID Phone Number', 'Source IP / Trunk', 'Test Status', 'Last Tested At']);

            $dids = BulkDid::orderBy('id', 'desc')->get();
            $serial = $dids->count();

            foreach ($dids as $did) {
                fputcsv($file, [
                    $serial--,
                    $did->phone_number,
                    $did->source_ip ?? '—',
                    strtoupper($did->status),
                    $did->last_tested_at ? $did->last_tested_at->format('Y-m-d H:i:s') : 'Not Tested',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
