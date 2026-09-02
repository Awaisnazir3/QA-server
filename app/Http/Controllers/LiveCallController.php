<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AsteriskService;

class LiveCallController extends Controller
{
    protected $asterisk;

    public function __construct(AsteriskService $asterisk)
    {
        $this->asterisk = $asterisk;
    }
    
    /**
     * Display live active calls
     */
    public function index()
    {
        $parsedCalls = $this->getActiveChannels();

        return view('calls.live', [
            'calls' => $parsedCalls,
            'callCount' => count($parsedCalls),
        ]);
    }

    /**
     * Handle hangup of all calls belonging to the logged-in user
     */
    public function hangupAll(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $userCalls = $this->getActiveChannels();
            foreach ($userCalls as $call) {
                $channel = preg_replace('/[^a-zA-Z0-9\/\-@_\.;]/', '', $call['channel']);
                $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'channel request hangup " . escapeshellarg($channel) . "' 2>/dev/null");
            }
        }

        return redirect()->route('calls.live');
    }

    /**
     * Handle hangup of single channel
     */
    public function hangupChannel(Request $request)
    {
        $channel = $request->input('channel');

        if (!empty($channel)) {
            $channel = preg_replace('/[^a-zA-Z0-9\/\-@_\.;]/', '', $channel);
            
            // Verify that this channel belongs to softswitch DIDs
            $userDids = \App\Models\CallLog::withoutGlobalScopes()->pluck('phone_number')
                ->map(function($num) { return preg_replace('/[^0-9]/', '', $num); })
                ->filter()
                ->toArray();
                
            $belongsToUser = false;
            foreach ($userDids as $did) {
                if (strpos($channel, $did) !== false) {
                    $belongsToUser = true;
                    break;
                }
            }

            if ($belongsToUser) {
                $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'channel request hangup " . escapeshellarg($channel) . "' 2>/dev/null");
            } else {
                return redirect()->route('calls.live')->with('error', 'Unauthorized channel hangup attempt.');
            }
        }

        return redirect()->route('calls.live');
    }

    /**
     * Parse channels from Asterisk CLI output and filter by user DIDs
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

        // Filter calls to only show channels corresponding to softswitch DIDs (across all scopes)
        $userDids = \App\Models\CallLog::withoutGlobalScopes()->pluck('phone_number')
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
}
