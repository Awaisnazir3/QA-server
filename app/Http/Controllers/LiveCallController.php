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
     * Handle hangup of all calls
     */
    public function hangupAll(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");
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
            $this->asterisk->execute("sudo /usr/sbin/asterisk -rx 'channel request hangup " . escapeshellarg($channel) . "' 2>/dev/null");
        }

        return redirect()->route('calls.live');
    }

    /**
     * Parse channels from Asterisk CLI output
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

        return $parsedCalls;
    }
}
