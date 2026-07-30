<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\ChannelTestLog;
use App\Models\ChannelTestCdr;
use Illuminate\Http\Request;

class ChannelTestController extends Controller
{
    /**
     * Display channel test history
     */
    public function index()
    {
        $history = ChannelTestLog::orderBy('created_at', 'desc')->limit(100)->get();

        return view('tests.channel-history', [
            'history' => $history,
        ]);
    }

    /**
     * Execute channel test for a DID
     */
    public function test(Request $request, CallLog $callLog)
    {
        // Verify DID status is PASS
        if (strtolower(trim($callLog->status)) !== 'pass') {
            return redirect()->route('dashboard')
                ->with('error', 'DID status is not PASS. Channel test blocked.');
        }

        $callCount = $request->input('call_count', 5);
        $callCount = max(1, min(100, (int)$callCount));

        $phoneNumber = preg_replace('/[^0-9+]/', '', $callLog->phone_number);

        if (empty($phoneNumber) || $callLog->id <= 0) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid phone number or DID.');
        }

        // Update call log status
        $callLog->update(['caller_name' => 'channel_test_active']);

        // Execute calls with 10-second delay between each
        for ($i = 0; $i < $callCount; $i++) {
            $callNum = $i + 1;
            $originalCallerId = "ChTest-{$callNum} <{$phoneNumber}>";

            $cmd = "sudo /usr/sbin/asterisk -rx "
                 . "'originate Local/{$phoneNumber}@outbound7788/n"
                 . " application WaitExten 600"
                 . " callerid \"{$originalCallerId}\""
                 . "' >> /tmp/chtest_{$phoneNumber}.log 2>&1 &";

            @shell_exec($cmd);

            // Insert individual call CDR record
            ChannelTestCdr::create([
                'did_id' => $callLog->id,
                'phone_number' => $phoneNumber,
                'caller_id' => $phoneNumber,
                'call_status' => 'Answered',
            ]);

            // Wait 10 seconds before next call (except after last call)
            if ($i < $callCount - 1) {
                sleep(10);
            }
        }

        sleep(20);

        // Count active channels detected
        $activeChannelsDetected = $this->countActiveChannels($phoneNumber);

        // Hangup all channels
        @shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup Local/{$phoneNumber}@outbound7788' 2>/dev/null");
        @shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");

        // Update call log with results
        $callLog->update([
            'checked_channels' => $activeChannelsDetected,
            'caller_name' => null,
        ]);

        // Log entry into channel_test_logs for history
        ChannelTestLog::create([
            'did_id' => $callLog->id,
            'phone_number' => $phoneNumber,
            'calls_requested' => $callCount,
            'channels_detected' => $activeChannelsDetected,
            'status' => 'completed',
        ]);

        return redirect()->route('dashboard')
            ->with('success', "Channel test completed. Channels detected: {$activeChannelsDetected}");
    }

    /**
     * Count active channels detected for a phone number
     */
    private function countActiveChannels(string $phoneNumber): int
    {
        $channelVerbose = @shell_exec("sudo /usr/sbin/asterisk -rx 'core show channels verbose' 2>/dev/null");
        $count = 0;

        if ($channelVerbose) {
            $lines = explode("\n", $channelVerbose);
            foreach ($lines as $line) {
                if (strpos($line, 'channel-test-hold') !== false
                    && strpos($line, $phoneNumber) !== false
                    && strpos($line, 'SIP/') !== false) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
