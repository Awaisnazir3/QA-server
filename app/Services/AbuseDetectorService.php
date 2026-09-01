<?php

namespace App\Services;

use App\Models\AbuseDid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AbuseDetectorService
{
    protected AsteriskService $asterisk;

    public function __construct(AsteriskService $asterisk)
    {
        $this->asterisk = $asterisk;
        AbuseDid::ensureTableExists();
    }

    /**
     * Fetch recent logs from Asterisk and process new abuse hits
     */
    public function scanAndProcessLogs(?string $customLogContent = null): array
    {
        $logContent = $customLogContent;

        if ($logContent === null) {
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            if (!$isWindows) {
                // On Linux production server: Read directly from local Asterisk log files (<1ms)
                $cmd = "tail -n 500 /var/log/asterisk/messages 2>/dev/null || "
                     . "tail -n 500 /var/log/asterisk/full 2>/dev/null || "
                     . "tail -n 500 /var/log/asterisk/messages.log 2>/dev/null || "
                     . "journalctl -u asterisk -n 300 --no-pager 2>/dev/null || true";

                $logContent = @shell_exec($cmd);

                // Direct file reading fallback if shell_exec is restricted
                if (empty(trim($logContent ?? ''))) {
                    $logFiles = [
                        '/var/log/asterisk/messages',
                        '/var/log/asterisk/full',
                        '/var/log/asterisk/messages.log',
                        '/var/log/asterisk/debug',
                    ];
                    foreach ($logFiles as $lf) {
                        if (@file_exists($lf) && @is_readable($lf)) {
                            $lines = @file($lf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            if ($lines && count($lines) > 0) {
                                $logContent .= "\n" . implode("\n", array_slice($lines, -300));
                            }
                        }
                    }
                }
            } else {
                // On Windows dev machine: Execute via AsteriskService
                try {
                    $logContent = $this->asterisk->execute('tail -n 300 /var/log/asterisk/messages 2>/dev/null || tail -n 300 /var/log/asterisk/full 2>/dev/null');
                } catch (\Throwable $e) {
                    $logContent = '';
                }
            }
        }

        if (empty(trim($logContent ?? ''))) {
            return [
                'new_hits' => 0,
                'updated_dids' => [],
                'recent_logs' => [],
            ];
        }

        return $this->parseLogContent($logContent);
    }

    /**
     * Parse raw log text and extract call events
     */
    public function parseLogContent(string $rawLogs): array
    {
        AbuseDid::ensureTableExists();
        $lines = explode("\n", $rawLogs);
        $callEvents = [];
        $recentLogLines = [];

        // Track processed call tokens in cache for 2 hours to avoid duplicate counting of same call instance
        $processedCalls = Cache::get('processed_abuse_call_ids', []);
        if (!is_array($processedCalls)) {
            $processedCalls = [];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                continue;
            }

            // Save line for recent logs feed
            if (stripos($trimmed, 'whitelist') !== false || stripos($trimmed, 'outbound') !== false || stripos($trimmed, 'NOTICE') !== false || stripos($trimmed, 'Executing') !== false) {
                $recentLogLines[] = $trimmed;
            }

            // Timestamp
            $timestamp = null;
            if (preg_match('/^\[([A-Za-z]{3}\s+\d+\s+\d{2}:\d{2}:\d{2})\]/', $trimmed, $tsMatch)) {
                try {
                    $timestamp = Carbon::parse($tsMatch[1]);
                } catch (\Throwable $e) {
                    $timestamp = now();
                }
            }

            // Call ID e.g. [C-00000698]
            $callId = null;
            if (preg_match('/\[C-([0-9a-fA-F]+)\]/', $trimmed, $cidMatch)) {
                $callId = 'C-' . $cidMatch[1];
            }

            // Channel Hex ID e.g. PJSIP/eu3.didx.net-00000697
            $chanHex = null;
            if (preg_match('/(?:PJSIP|SIP)\/[a-zA-Z0-9\.\-_]+-([0-9a-fA-F]{6,12})/i', $trimmed, $pjsipMatch)) {
                $chanHex = 'CHAN-' . $pjsipMatch[1];
            }

            // Trunk / Peer name e.g. eu3.didx.net
            $trunk = null;
            if (preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s)/i', $trimmed, $tMatch)) {
                $trunk = $tMatch[1];
            }

            // Phone / DID extraction
            $phone = null;
            if (preg_match('/Executing\s+\[\s*(\+?[0-9]{3,20})\s*@/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/(?:check_whitelist\.php|agi)[\s,]+(\+?[0-9]{3,20})/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/Ext\.\s*(\+?[0-9]{3,20}):/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/Checking Whitelist.*?for\s+(\+?[0-9]{3,20})/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/NOTICE.*?\b(\+?[0-9]{4,20})\b.*?handled or rejected/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/Spawn extension \([a-zA-Z0-9_\-]+,\s*(\+?[0-9]{3,20}),/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            }

            // When a phone number is detected in this line
            if ($phone) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                if (strlen($cleanPhone) >= 4) {
                    // Token based on this line's channel hex or call ID or line hash
                    $token = $chanHex ?: $callId ?: ($cleanPhone . '_' . ($trunk ?: 'inbound') . '_' . substr(md5($trimmed), 0, 8));

                    if (!isset($callEvents[$token])) {
                        $callEvents[$token] = [
                            'call_id' => $callId ?: $chanHex,
                            'phone_number' => $cleanPhone,
                            'source_trunk' => $trunk ?: 'Asterisk-Inbound',
                            'timestamp' => $timestamp ?: now(),
                            'raw_line' => $trimmed,
                            'status' => 'rejected',
                        ];
                    } else {
                        // Update with more specific trunk/call_id if previously generic
                        if ($trunk && $callEvents[$token]['source_trunk'] === 'Asterisk-Inbound') {
                            $callEvents[$token]['source_trunk'] = $trunk;
                        }
                        if ($callId && empty($callEvents[$token]['call_id'])) {
                            $callEvents[$token]['call_id'] = $callId;
                        }
                    }
                }
            }
        }

        $newHitsCount = 0;
        $updatedDids = [];

        // Save each detected call event to Database
        foreach ($callEvents as $token => $event) {
            // Check if this call instance was already processed
            if (isset($processedCalls[$token])) {
                continue;
            }

            $phone = $event['phone_number'];
            $trunk = $event['source_trunk'];
            $hitTime = $event['timestamp'] instanceof Carbon ? $event['timestamp'] : Carbon::parse($event['timestamp']);

            // Find existing DID in abuse list or create new
            $abuseDid = AbuseDid::where('phone_number', $phone)->first();

            if ($abuseDid) {
                // Increment hits count (1 -> 2 -> 3 -> 4 ...)
                $abuseDid->hits_count = ($abuseDid->hits_count ?? 1) + 1;
                $abuseDid->last_hit_at = $hitTime;
                if (!empty($trunk) && $trunk !== 'Asterisk-Inbound') {
                    $abuseDid->source_trunk = $trunk;
                }
                $abuseDid->last_call_id = $event['call_id'];
                $abuseDid->raw_log = $event['raw_line'];
                $abuseDid->save();
            } else {
                // Create new Abuse DID record with 1 hit
                $abuseDid = AbuseDid::create([
                    'phone_number' => $phone,
                    'source_trunk' => $trunk,
                    'hits_count' => 1,
                    'status' => 'rejected',
                    'first_hit_at' => $hitTime,
                    'last_hit_at' => $hitTime,
                    'last_call_id' => $event['call_id'],
                    'raw_log' => $event['raw_line'],
                ]);
            }

            // Mark token as processed
            $processedCalls[$token] = time();
            $newHitsCount++;
            $updatedDids[$phone] = [
                'id' => $abuseDid->id,
                'phone_number' => $abuseDid->phone_number,
                'hits_count' => $abuseDid->hits_count,
                'source_trunk' => $abuseDid->source_trunk,
                'last_hit_at' => $abuseDid->last_hit_at ? $abuseDid->last_hit_at->format('Y-m-d H:i:s') : '',
                'status' => $abuseDid->status,
            ];
        }

        // Limit processed tokens cache size
        if (count($processedCalls) > 5000) {
            $processedCalls = array_slice($processedCalls, -2500, null, true);
        }
        Cache::put('processed_abuse_call_ids', $processedCalls, 7200);

        return [
            'new_hits' => $newHitsCount,
            'updated_dids' => $updatedDids,
            'recent_logs' => array_slice(array_reverse($recentLogLines), 0, 30),
        ];
    }
}
