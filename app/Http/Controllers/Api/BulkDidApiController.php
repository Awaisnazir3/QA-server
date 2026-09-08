<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkDid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkDidApiController extends Controller
{
    /**
     * Clean phone number by removing non-numeric characters and leading zeroes
     */
    private function cleanPhone(string $number): string
    {
        $digits = preg_replace('/[^0-9]/', '', $number);
        return ltrim($digits, '0');
    }

    /**
     * Find a BulkDid record by raw or cleaned phone number
     */
    private function findBulkDid(string $rawPhone): ?BulkDid
    {
        $rawPhone = trim($rawPhone);
        if (empty($rawPhone)) {
            return null;
        }

        // 1. Exact match query
        $record = BulkDid::where('phone_number', $rawPhone)
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
        $candidates = BulkDid::where('phone_number', 'LIKE', '%' . substr($cleanQuery, -7))
            ->latest('id')
            ->get();

        foreach ($candidates as $cand) {
            if ($this->cleanPhone($cand->phone_number) === $cleanQuery) {
                return $cand;
            }
        }

        // 3. Fallback scan if needed
        return BulkDid::all()->first(function ($cand) use ($cleanQuery) {
            return $this->cleanPhone($cand->phone_number) === $cleanQuery;
        });
    }

    /**
     * Format BulkDid model into consistent API response array
     */
    private function formatResponse(string $requestedDid, ?BulkDid $bulkDid): array
    {
        if (!$bulkDid) {
            return [
                'did'            => $requestedDid,
                'found'          => false,
                'status'         => 'not_found',
                'source_ip'      => null,
                'last_tested_at' => null,
            ];
        }

        return [
            'did'            => $bulkDid->phone_number,
            'requested_did'  => $requestedDid,
            'found'          => true,
            'status'         => strtolower($bulkDid->status ?: 'pending'),
            'source_ip'      => $bulkDid->source_ip ?: null,
            'last_tested_at' => $bulkDid->last_tested_at ? $bulkDid->last_tested_at->toDateTimeString() : null,
            'created_at'     => $bulkDid->created_at ? $bulkDid->created_at->toDateTimeString() : null,
            'updated_at'     => $bulkDid->updated_at ? $bulkDid->updated_at->toDateTimeString() : null,
        ];
    }

    /**
     * Check status of a single DID (or batch if array provided)
     * 
     * Supports:
     * - GET /api/bulk-did/check?did=1234567890
     * - GET /api/bulk-did/check?phone_number=1234567890
     * - POST /api/bulk-did/check {"did": "1234567890"}
     * - POST /api/bulk-did/check {"dids": ["1234567890", "0987654321"]}
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

        $record = $this->findBulkDid((string)$did);
        $result = $this->formatResponse((string)$did, $record);

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Check status of a DID via route parameter
     * 
     * Example: GET /api/bulk-did/status/{did}
     */
    public function getStatusByParam(string $did): JsonResponse
    {
        if (empty(trim($did))) {
            return response()->json([
                'success' => false,
                'message' => 'DID parameter is required.',
            ], 422);
        }

        $record = $this->findBulkDid($did);
        $result = $this->formatResponse($did, $record);

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Batch check status for multiple DIDs
     * 
     * Example: POST /api/bulk-did/batch-check {"dids": ["1234567890", "0987654321"]}
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
            $record = $this->findBulkDid($didStr);
            $results[] = $this->formatResponse($didStr, $record);
        }

        return response()->json([
            'success' => true,
            'total'   => count($results),
            'results' => $results,
        ]);
    }

    /**
     * List all bulk DID test records
     * 
     * Example: GET /api/bulk-did/list
     */
    public function listAll(Request $request): JsonResponse
    {
        $query = BulkDid::orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->query('status')));
        }

        $records = $query->get()->map(function ($did) {
            return [
                'id'             => $did->id,
                'phone_number'   => $did->phone_number,
                'status'         => strtolower($did->status ?: 'pending'),
                'source_ip'      => $did->source_ip ?: null,
                'last_tested_at' => $did->last_tested_at ? $did->last_tested_at->toDateTimeString() : null,
                'created_at'     => $did->created_at ? $did->created_at->toDateTimeString() : null,
                'updated_at'     => $did->updated_at ? $did->updated_at->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'total'   => $records->count(),
            'data'    => $records,
        ]);
    }
}
