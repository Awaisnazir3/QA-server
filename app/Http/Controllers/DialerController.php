<?php

namespace App\Http\Controllers;

use App\Models\CallHistory;
use App\Models\CallLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DialerController extends Controller
{
    /**
     * Show dialer interface (Softphone 63311 control)
     */
    public function index()
    {
        // Get active routes (outbound) - used as caller IDs
        $routes = CallLog::where('status', 'pass')->get();
        
        // Get recent call history
        $callHistory = CallHistory::orderBy('created_at', 'desc')->limit(50)->get();

        return view('operations.dialer', [
            'routes' => $routes,
            'callHistory' => $callHistory,
        ]);
    }

    /**
     * Make an outbound call via Asterisk Originate
     */
    public function makeCall(Request $request): JsonResponse
    {
        $request->validate([
            'caller_id' => 'required|string|regex:/^[0-9+]{1,15}$/',
            'callee_number' => 'required|string|regex:/^[0-9+]{1,15}$/',
            'extension' => 'nullable|string|regex:/^[0-9]{3,}$/',
            'route_id' => 'nullable|exists:call_logs,id',
        ]);

        $callerId = $request->input('caller_id');
        $calleeNumber = $request->input('callee_number');
        $extension = $request->input('extension', '63311'); // Default to softphone 63311
        $routeId = $request->input('route_id');

        // Get route/DID for this outbound call
        $route = null;
        if ($routeId) {
            $route = CallLog::find($routeId);
        } else {
            // Use first available route
            $route = CallLog::where('status', 'pass')->first();
        }

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'No active route available',
            ], 400);
        }

        // Create call history record
        $callHistory = CallHistory::create([
            'caller_id' => $callerId,
            'callee_number' => $calleeNumber,
            'direction' => 'outbound',
            'status' => 'pending',
            'route_id' => $route->id,
            'start_time' => now(),
        ]);

        // Execute Asterisk Originate command
        $channel = "PJSIP/{$route->phone_number}@outbound";
        $context = "from-internal";
        $exten = $calleeNumber;
        $priority = "1";

        $asteriskCmd = "sudo /usr/sbin/asterisk -rx 'channel originate " .
            escapeshellarg($channel) . " " .
            "application bridge " .
            escapeshellarg("SIP/{$callerId}") . "' 2>/dev/null";

        // On Windows, just simulate; on Linux, execute
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            @shell_exec($asteriskCmd);
        }

        // Update status to ringing
        $callHistory->update([
            'status' => 'ringing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call initiated',
            'call_id' => $callHistory->id,
            'caller_id' => $callerId,
            'callee_number' => $calleeNumber,
            'extension' => $extension,
        ]);
    }

    /**
     * End/hangup an active call
     */
    public function hangupCall(Request $request): JsonResponse
    {
        $request->validate([
            'call_id' => 'required|exists:call_histories,id',
        ]);

        $callId = $request->input('call_id');
        $callHistory = CallHistory::find($callId);

        if (!$callHistory) {
            return response()->json([
                'success' => false,
                'message' => 'Call not found',
            ], 404);
        }

        // Update call status and end time
        $callHistory->update([
            'status' => 'completed',
            'end_time' => now(),
            'duration' => $callHistory->start_time ? 
                now()->diffInSeconds($callHistory->start_time) : 0,
        ]);

        // Execute Asterisk hangup
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            @shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");
        }

        return response()->json([
            'success' => true,
            'message' => 'Call ended',
            'duration' => $callHistory->duration,
        ]);
    }

    /**
     * Get call history for display
     */
    public function getHistory(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $direction = $request->input('direction'); // 'inbound', 'outbound', or all

        $query = CallHistory::query();

        if ($direction && in_array($direction, ['inbound', 'outbound'])) {
            $query->where('direction', $direction);
        }

        $history = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'caller_id' => $call->caller_id,
                    'callee_number' => $call->callee_number,
                    'direction' => $call->direction,
                    'status' => $call->status,
                    'duration' => $call->duration ?? 0,
                    'start_time' => $call->start_time?->format('Y-m-d H:i:s'),
                    'route_id' => $call->route_id,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => count($history),
            'history' => $history,
        ]);
    }

    /**
     * Update call status (callback from Asterisk or webhook)
     */
    public function updateCallStatus(Request $request): JsonResponse
    {
        $request->validate([
            'call_id' => 'required|exists:call_histories,id',
            'status' => 'required|in:ringing,connected,completed,failed',
        ]);

        $callHistory = CallHistory::find($request->input('call_id'));
        $status = $request->input('status');

        $callHistory->update(['status' => $status]);

        if ($status === 'connected' && !$callHistory->start_time) {
            $callHistory->update(['start_time' => now()]);
        }

        if ($status === 'completed' || $status === 'failed') {
            if (!$callHistory->end_time) {
                $callHistory->update([
                    'end_time' => now(),
                    'duration' => $callHistory->start_time ? 
                        now()->diffInSeconds($callHistory->start_time) : 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Call status updated',
        ]);
    }

    /**
     * Record call notes
     */
    public function addNotes(Request $request): JsonResponse
    {
        $request->validate([
            'call_id' => 'required|exists:call_histories,id',
            'notes' => 'required|string|max:500',
        ]);

        $callHistory = CallHistory::find($request->input('call_id'));
        $callHistory->update(['notes' => $request->input('notes')]);

        return response()->json([
            'success' => true,
            'message' => 'Notes added',
        ]);
    }

    /**
     * Get active calls count
     */
    public function getActiveCalls(): JsonResponse
    {
        $activeCalls = CallHistory::whereIn('status', ['pending', 'ringing', 'connected'])
            ->count();

        return response()->json([
            'success' => true,
            'active_calls' => $activeCalls,
        ]);
    }

    /**
     * Get extension status (online/offline)
     */
    public function getExtensionStatus(Request $request): JsonResponse
    {
        $request->validate([
            'extension' => 'required|string|regex:/^[0-9]{3,}$/',
        ]);

        $extension = $request->input('extension');
        $status = 'unknown';
        $contact = '—';
        $registered = false;

        // On Windows, return mock status
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return response()->json([
                'success' => true,
                'extension' => $extension,
                'status' => 'online',
                'contact' => '192.168.1.100',
                'registered' => true,
            ]);
        }

        // On Linux, check real status
        $endpointsRaw = @shell_exec("sudo /usr/sbin/asterisk -rx 'pjsip show endpoints' 2>/dev/null");
        
        if ($endpointsRaw) {
            $lines = explode("\n", $endpointsRaw);
            $foundExt = false;
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (preg_match('/^Endpoint:\s+' . $extension . '\s+(.+?)\s+\d+\s+of/i', $line)) {
                    $foundExt = true;
                }
                
                // If we found the extension, look for Contact line
                if ($foundExt && preg_match('/^Contact:/i', $line)) {
                    if (preg_match('/sip:([0-9a-zA-Z.@:]+)/i', $line, $contactMatch)) {
                        $contact = $contactMatch[1];
                    }
                    
                    if (preg_match('/(Avail|Up|In use)/i', $line)) {
                        $status = 'online';
                        $registered = true;
                    } else if (preg_match('/(NonQual|Unavailable)/i', $line)) {
                        $status = 'offline';
                        $registered = false;
                    }
                    
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'extension' => $extension,
            'status' => $status,
            'contact' => $contact,
            'registered' => $registered,
        ]);
    }
}
