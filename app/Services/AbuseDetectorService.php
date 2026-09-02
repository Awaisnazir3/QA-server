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
     * Fetch recent logs from Asterisk and process new abuse hits without limits
     */
    public function scanAndProcessLogs(?string $customLogContent = null, bool $force = false): array
    {
        $logContent = $customLogContent;

        if ($logContent === null) {
            if (!$force) {
                // Throttle: only scan Asterisk log files at most once every 30 seconds
                $lastScan = Cache::get('last_abuse_log_scan_time', 0);
                if ((time() - $lastScan) < 30) {
                    return [
                        'new_hits' => 0,
                        'updated_dids' => [],
                        'recent_logs' => [],
                    ];
                }
            }

            Cache::put('last_abuse_log_scan_time', time(), 120);

            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            if (!$isWindows) {
                // On Linux production server: Read directly from local Asterisk log files (<1ms)
                $logContent = '';
                $logFiles = [
                    '/var/log/asterisk/full',
                    '/var/log/asterisk/messages',
                    '/var/log/asterisk/messages.log',
                    '/var/log/asterisk/debug',
                ];

                foreach ($logFiles as $lf) {
                    if (@file_exists($lf) && @is_readable($lf)) {
                        $lines = @file($lf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        if ($lines && count($lines) > 0) {
                            // Read up to 8000 lines without artificial clipping
                            $logContent .= "\n" . implode("\n", array_slice($lines, -8000));
                        }
                    }
                }

                if (empty(trim($logContent))) {
                    $cmd = "tail -n 8000 /var/log/asterisk/full 2>/dev/null; tail -n 5000 /var/log/asterisk/messages 2>/dev/null; journalctl -u asterisk -n 2000 --no-pager 2>/dev/null || true";
                    $logContent = @shell_exec($cmd);
                }
            } else {
                // On Windows dev machine: Execute via AsteriskService
                try {
                    $logContent = $this->asterisk->execute('tail -n 5000 /var/log/asterisk/full 2>/dev/null || tail -n 5000 /var/log/asterisk/messages 2>/dev/null');
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
     * Parse raw log text and extract call events accurately without hit limits
     */
    public function parseLogContent(string $rawLogs): array
    {
        AbuseDid::ensureTableExists();
        $lines = explode("\n", $rawLogs);
        $callEvents = [];
        $recentLogLines = [];

        // Track processed call tokens in cache for 2 hours to avoid duplicate counting of same call instance
        $processedCalls = [];
        try {
            $cached = Cache::get('processed_abuse_call_ids', []);
            if (is_array($cached)) {
                $processedCalls = $cached;
            }
        } catch (\Throwable $e) {
            $processedCalls = [];
        }

        $currentActiveChannel = null;
        $currentActivePhone = null;
        $currentActiveTrunk = null;
        $currentActiveTimestamp = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                continue;
            }

            // Save line for recent logs feed if relevant
            if (stripos($trimmed, 'whitelist') !== false || stripos($trimmed, 'outbound') !== false || stripos($trimmed, 'NOTICE') !== false || stripos($trimmed, 'Executing') !== false || stripos($trimmed, 'Spawn extension') !== false || stripos($trimmed, 'AGI') !== false) {
                $recentLogLines[] = $trimmed;
            }

            // Timestamp extraction
            $timestamp = null;
            if (preg_match('/^\[([A-Za-z]{3}\s+\d+\s+\d{2}:\d{2}:\d{2})\]/', $trimmed, $tsMatch)) {
                try {
                    $timestamp = Carbon::parse($tsMatch[1]);
                } catch (\Throwable $e) {
                    $timestamp = now();
                }
            }

            // Call ID e.g. [C-0000081b]
            $callId = null;
            if (preg_match('/\[C-([0-9a-fA-F]+)\]/', $trimmed, $cidMatch)) {
                $callId = 'C-' . strtolower($cidMatch[1]);
            }

            // Channel Hex ID e.g. PJSIP/eu3.didx.net-0000081e -> CHAN-0000081e
            $chanHex = null;
            if (preg_match('/(?:PJSIP|SIP)\/[a-zA-Z0-9\.\-_]+-([0-9a-fA-F]{6,12})/i', $trimmed, $pjsipMatch)) {
                $chanHex = 'CHAN-' . strtolower($pjsipMatch[1]);
            }

            // Trunk / Peer name e.g. eu3.didx.net
            $trunk = null;
            if (preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s)/i', $trimmed, $tMatch)) {
                $trunk = $tMatch[1];
            }

            // Phone / DID extraction - Capture ANY phone number / DID hitting Asterisk (2 to 32 digits)
            $phone = null;
            if (preg_match('/Executing\s+\[\s*([0-9\+]{2,32})\s*@/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/(?:check_whitelist\.php|agi)[\s,("]+([0-9\+]{2,32})/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/Ext\.\s*([0-9\+]{2,32}):/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/Checking\s+Whitelist.*?for\s+([0-9\+]{2,32})/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/NOTICE.*?\b([0-9\+]{2,32})\b.*?handled or rejected/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/Spawn\s+extension\s*\([^,]+,\s*([0-9\+]{2,32})\s*,\s*\d+\)/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            } elseif (preg_match('/to\s+extension\s+[\'"]?([0-9\+]{2,32})[\'"]?/i', $trimmed, $pMatch)) {
                $phone = $pMatch[1];
            }

            $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;
            if ($cleanPhone && strlen($cleanPhone) < 2) {
                $cleanPhone = null;
            }

            // Update active channel context
            if ($chanHex) {
                $currentActiveChannel = $chanHex;
                if ($cleanPhone) $currentActivePhone = $cleanPhone;
                if ($trunk) $currentActiveTrunk = $trunk;
                if ($timestamp) $currentActiveTimestamp = $timestamp;
            }

            // Call event construction & correlation
            if ($chanHex && $cleanPhone) {
                $token = $chanHex;
                if (!isset($callEvents[$token])) {
                    $callEvents[$token] = [
                        'token' => $token,
                        'phone_number' => $cleanPhone,
                        'source_trunk' => $trunk ?: 'Asterisk-Inbound',
                        'timestamp' => $timestamp ?: ($currentActiveTimestamp ?: now()),
                        'call_id' => $callId,
                        'raw_line' => $trimmed,
                        'status' => 'rejected',
                    ];
                } else {
                    if ($trunk && $callEvents[$token]['source_trunk'] === 'Asterisk-Inbound') {
                        $callEvents[$token]['source_trunk'] = $trunk;
                    }
                    if ($callId && empty($callEvents[$token]['call_id'])) {
                        $callEvents[$token]['call_id'] = $callId;
                    }
                    if ($timestamp) {
                        $callEvents[$token]['timestamp'] = $timestamp;
                    }
                }
            } elseif ($callId && $cleanPhone) {
                // If this Call ID matches the active channel and same phone, correlate without creating duplicate
                if ($currentActiveChannel && $currentActivePhone === $cleanPhone && isset($callEvents[$currentActiveChannel])) {
                    $callEvents[$currentActiveChannel]['call_id'] = $callId;
                    if ($timestamp) {
                        $callEvents[$currentActiveChannel]['timestamp'] = $timestamp;
                    }
                } else {
                    $token = $callId;
                    if (!isset($callEvents[$token])) {
                        $callEvents[$token] = [
                            'token' => $token,
                            'phone_number' => $cleanPhone,
                            'source_trunk' => $currentActiveTrunk ?: 'Asterisk-Inbound',
                            'timestamp' => $timestamp ?: now(),
                            'call_id' => $callId,
                            'raw_line' => $trimmed,
                            'status' => 'rejected',
                        ];
                    }
                }
            } elseif ($cleanPhone) {
                $token = $currentActiveChannel ?: ($cleanPhone . '_' . substr(md5($trimmed), 0, 8));
                if (!isset($callEvents[$token])) {
                    $callEvents[$token] = [
                        'token' => $token,
                        'phone_number' => $cleanPhone,
                        'source_trunk' => $trunk ?: ($currentActiveTrunk ?: 'Asterisk-Inbound'),
                        'timestamp' => $timestamp ?: ($currentActiveTimestamp ?: now()),
                        'call_id' => null,
                        'raw_line' => $trimmed,
                        'status' => 'rejected',
                    ];
                }
            }
        }

        $newHitsCount = 0;
        $updatedDids = [];

        // 1. Gather all unprocessed events and unique phone numbers
        $unprocessedEvents = [];
        foreach ($callEvents as $token => $event) {
            if (!isset($processedCalls[$token])) {
                $unprocessedEvents[$token] = $event;
            }
        }

        if (empty($unprocessedEvents)) {
            return [
                'new_hits' => 0,
                'updated_dids' => [],
                'recent_logs' => array_slice(array_reverse($recentLogLines), 0, 50),
            ];
        }

        $distinctPhones = array_values(array_unique(array_column($unprocessedEvents, 'phone_number')));
        $existingDids = AbuseDid::whereIn('phone_number', $distinctPhones)->get()->keyBy('phone_number');

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($unprocessedEvents as $token => $event) {
                $phone = $event['phone_number'];
                $trunk = $event['source_trunk'];
                $hitTime = $event['timestamp'] instanceof Carbon ? $event['timestamp'] : Carbon::parse($event['timestamp']);

                $abuseDid = $existingDids->get($phone);

                if ($abuseDid) {
                    $abuseDid->hits_count = ($abuseDid->hits_count ?? 1) + 1;
                    $abuseDid->last_hit_at = $hitTime;
                    if (!empty($trunk) && $trunk !== 'Asterisk-Inbound') {
                        $abuseDid->source_trunk = $trunk;
                    }
                    if (!empty($event['call_id'])) {
                        $abuseDid->last_call_id = $event['call_id'];
                    }
                    $abuseDid->raw_log = $event['raw_line'];
                    $abuseDid->save();
                } else {
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
                    $existingDids->put($phone, $abuseDid);
                }

                $processedCalls[$token] = time();
                $newHitsCount++;
                $updatedDids[$phone] = [
                    'id' => $abuseDid->id,
                    'phone_number' => $abuseDid->phone_number,
                    'hits_count' => (int) $abuseDid->hits_count,
                    'source_trunk' => $abuseDid->source_trunk,
                    'last_hit_at' => $abuseDid->last_hit_at ? $abuseDid->last_hit_at->format('Y-m-d H:i:s') : '',
                    'status' => $abuseDid->status,
                ];
            }
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error("Failed to save abuse call events: " . $e->getMessage());
        }

        // Limit processed tokens cache size to prevent memory leak while holding up to 20,000 recent call tokens
        if (count($processedCalls) > 20000) {
            $processedCalls = array_slice($processedCalls, -10000, null, true);
        }
        try {
            Cache::put('processed_abuse_call_ids', $processedCalls, 7200);
        } catch (\Throwable $e) {
            // Ignore cache write error
        }

        return [
            'new_hits' => $newHitsCount,
            'updated_dids' => $updatedDids,
            'recent_logs' => array_slice(array_reverse($recentLogLines), 0, 50),
        ];
    }
}
