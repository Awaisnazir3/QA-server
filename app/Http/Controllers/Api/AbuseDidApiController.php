<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbuseDid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbuseDidApiController extends Controller
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
                'source_trunk'  => null,
                'first_hit_at'  => null,
                'last_hit_at'   => null,
            ];
        }

        return [
            'did'           => $abuseDid->phone_number,
            'requested_did' => $requestedDid,
            'found'         => true,
            'status'        => 'PASS',
            'hits_count'    => (int) $abuseDid->hits_count,
            'source_trunk'  => $abuseDid->source_trunk ?: 'Asterisk-Inbound',
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
            return [
                'id'            => $did->id,
                'phone_number'  => $did->phone_number,
                'status'        => 'PASS',
                'hits_count'    => (int) $did->hits_count,
                'source_trunk'  => $did->source_trunk ?: 'Asterisk-Inbound',
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
