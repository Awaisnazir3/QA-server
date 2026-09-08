#!/usr/bin/php -q
<?php
/**
 * Asterisk AGI - Whitelist Checker, Router & Real-Time Abuse Detector
 * Location on Asterisk Server: /var/lib/asterisk/agi-bin/check_whitelist.php
 * Permissions: chmod +x /var/lib/asterisk/agi-bin/check_whitelist.php
 */

@ignore_user_abort(true);
@set_time_limit(5);

// Database Configuration
$dbHost = '127.0.0.1';
$dbUser = 'admin';
$dbPass = '12343211';
$dbName = 'telecom_db';

// Helper function to send AGI command and read response
function agiCommand($cmd) {
    @fputs(STDOUT, trim($cmd) . "\n");
    @fflush(STDOUT);
    $resp = @fgets(STDIN);
    return $resp ? trim($resp) : '';
}

// Helper function to set variable in Asterisk channel via both AGI and dialplan application
function setAgiChannelVar($name, $val) {
    agiCommand("SET VARIABLE {$name} {$val}");
    agiCommand("EXEC Set {$name}={$val}");
}

// 1. Read AGI Environment variables from Asterisk
$agi = [];
while (!feof(STDIN)) {
    $line = trim(fgets(STDIN));
    if ($line === '') {
        break;
    }
    if (strpos($line, ':') !== false) {
        list($key, $val) = explode(':', $line, 2);
        $agi[strtolower(trim($key))] = trim($val);
    }
}

// 2. Extract DID from AGI arguments ($argv[1]) or agi_extension / agi_dnid / agi_callerid
$didNumber = '';
if (isset($argv[1]) && !empty(trim($argv[1]))) {
    $didNumber = trim($argv[1]);
} elseif (isset($agi['agi_extension']) && !empty($agi['agi_extension']) && $agi['agi_extension'] !== 's') {
    $didNumber = $agi['agi_extension'];
} elseif (isset($agi['agi_dnid']) && !empty($agi['agi_dnid']) && $agi['agi_dnid'] !== 's') {
    $didNumber = $agi['agi_dnid'];
} elseif (isset($agi['agi_callerid']) && !empty($agi['agi_callerid'])) {
    $didNumber = $agi['agi_callerid'];
}

$cleanDid = preg_replace('/[^0-9]/', '', $didNumber);

// 3. Extract Channel name from all available sources
$channel = $agi['agi_channel'] ?? ($_SERVER['agi_channel'] ?? ($_ENV['agi_channel'] ?? (getenv('agi_channel') ?: '')));

if (empty($channel) || $channel === 'Asterisk-Inbound') {
    $resp = agiCommand("GET VARIABLE CHANNEL");
    if ($resp && preg_match('/\((.+?)\)/', $resp, $rm) && !empty(trim($rm[1]))) {
        $channel = trim($rm[1]);
    }
}

// 4. Resolve Dynamic Trunk Name / DNS
$trunkName = 'Asterisk-Inbound';

if (preg_match('/(?:PJSIP|SIP|IAX2|DAHDI)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s|$)/i', $channel, $m)) {
    $trunkName = $m[1];
} elseif (isset($argv[2]) && !empty(trim($argv[2]))) {
    $trunkName = trim($argv[2]);
}

if ($trunkName === 'Asterisk-Inbound') {
    $allInputs = implode(' ', [
        $channel,
        $argv[2] ?? '',
        $argv[1] ?? '',
        $agi['agi_channel'] ?? '',
        $agi['agi_request'] ?? '',
        json_encode($agi)
    ]);
    if (preg_match('/(?:PJSIP|SIP|IAX2)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s|$)/i', $allInputs, $m)) {
        $trunkName = $m[1];
    }
}

if ($trunkName === 'Asterisk-Inbound' && !empty($cleanDid) && PHP_OS_FAMILY !== 'Windows') {
    $escaped = escapeshellarg($cleanDid);
    $grepOut = @shell_exec("grep -F {$escaped} /var/log/asterisk/messages 2>/dev/null | tail -n 10");
    if ($grepOut && preg_match('/(?:PJSIP|SIP|IAX2)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s)/i', $grepOut, $gm)) {
        $trunkName = $gm[1];
    }
}

// 5. Resolve Source IP dynamically
$trunkIpMap = [
    'sip10.didx.net' => '198.211.99.232',
    'eu2.didx.net' => '178.62.98.165',
    'eu3.didx.net' => '46.101.28.27',
    'ca.didx.net' => '68.183.206.46',
    'us2.didx.net' => '162.243.253.22',
    'belloceanic' => '139.59.2.249',
    'Sip.belloceanic.com' => '139.59.2.249',
    'sip.belloceanic.com' => '139.59.2.249',
    'VPL-Switch' => '104.131.49.119',
];

$sourceIp = '';
if (filter_var($trunkName, FILTER_VALIDATE_IP)) {
    $sourceIp = $trunkName;
} elseif (isset($trunkIpMap[$trunkName])) {
    $sourceIp = $trunkIpMap[$trunkName];
} elseif ($trunkName !== 'Asterisk-Inbound') {
    $resolved = @gethostbyname($trunkName);
    if ($resolved && $resolved !== $trunkName && filter_var($resolved, FILTER_VALIDATE_IP)) {
        $sourceIp = $resolved;
    }
}

$callId = $agi['agi_uniqueid'] ?? ($_SERVER['agi_uniqueid'] ?? null);
$callerId = $agi['agi_callerid'] ?? ($agi['agi_calleridname'] ?? '');
if (in_array(strtolower(trim($callerId)), ['<unknown>', '(null)', 'none', 'unknown', '—', ''])) {
    $callerId = '';
}

// 6. REAL-TIME ROUTE CHECKING, DB UPDATES & DIALPLAN ROUTE PROPAGATION
if (!empty($cleanDid) && strlen($cleanDid) >= 2) {
    try {
        $db = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($db && !$db->connect_error) {
            $escapedDid = $db->real_escape_string($cleanDid);
            $escapedTrunk = $db->real_escape_string($trunkName);
            $escapedIp = $db->real_escape_string($sourceIp);
            $escapedCallId = $callId ? "'" . $db->real_escape_string($callId) . "'" : "NULL";
            $escapedCallerId = $db->real_escape_string($callerId);

            $last8 = strlen($cleanDid) >= 8 ? substr($cleanDid, -8) : $cleanDid;
            $escapedLast8 = $db->real_escape_string($last8);

            // A. Check if DID is configured with ROUTE status or 7788 destination in call_logs
            $isRouted = false;
            $routeDestination = '7788';

            $chkQuery = "SELECT id, phone_number, status, route_destination FROM call_logs 
                         WHERE phone_number = '{$escapedDid}' 
                            OR phone_number = '+{$escapedDid}' 
                            OR phone_number = '00{$escapedDid}' 
                            OR REPLACE(REPLACE(phone_number, '+', ''), ' ', '') = '{$escapedDid}' 
                            OR phone_number LIKE '%{$escapedDid}%'
                            OR phone_number LIKE '%{$escapedLast8}%'
                         ORDER BY (CASE WHEN status = 'route' THEN 1 WHEN status = 'pass' THEN 2 ELSE 3 END) ASC 
                         LIMIT 1";

            $chkRes = @$db->query($chkQuery);
            if ($chkRes && $row = $chkRes->fetch_assoc()) {
                $statusCheck = strtolower(trim($row['status'] ?? ''));
                if (!empty($row['route_destination'])) {
                    $routeDestination = trim($row['route_destination']);
                }
                if ($statusCheck === 'route' || !empty($row['route_destination'])) {
                    $isRouted = true;
                }
            }

            $abuseStatus = $isRouted ? 'routed' : 'rejected';
            $escapedRouteDest = $db->real_escape_string($routeDestination);

            // B. Atomic Insert / Increment in abuse_dids table
            $query = "INSERT INTO abuse_dids 
                (phone_number, source_trunk, source_ip, hits_count, status, first_hit_at, last_hit_at, last_call_id, created_at, updated_at) 
                VALUES ('{$escapedDid}', '{$escapedTrunk}', '{$escapedIp}', 1, '{$abuseStatus}', NOW(), NOW(), {$escapedCallId}, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    hits_count = hits_count + 1, 
                    last_hit_at = NOW(), 
                    status = IF('{$abuseStatus}' = 'routed', 'routed', status),
                    source_trunk = IF(VALUES(source_trunk) != '' AND VALUES(source_trunk) != 'Asterisk-Inbound', VALUES(source_trunk), source_trunk),
                    source_ip = IF(VALUES(source_ip) != '', VALUES(source_ip), source_ip),
                    last_call_id = IF(VALUES(last_call_id) IS NOT NULL, VALUES(last_call_id), last_call_id),
                    updated_at = NOW()";

            @$db->query($query);

            // C. Update status to ROUTE (if routed) or PASS (if not routed) in call_logs table (DID Routes Tab)
            $callLogStatus = $isRouted ? 'route' : 'pass';
            $updateCallLogQuery = "UPDATE call_logs 
                SET 
                    status = '{$callLogStatus}',
                    route_destination = IF('{$callLogStatus}' = 'route', '{$escapedRouteDest}', route_destination),
                    source_ip = CASE 
                        WHEN '{$escapedTrunk}' != '' AND '{$escapedTrunk}' != 'Asterisk-Inbound' THEN '{$escapedTrunk}'
                        WHEN source_ip IS NULL OR source_ip = '' OR source_ip = '—' THEN '{$escapedTrunk}'
                        ELSE source_ip
                    END,
                    caller_id = CASE 
                        WHEN '{$escapedCallerId}' != '' THEN '{$escapedCallerId}'
                        ELSE caller_id
                    END,
                    call_datetime = NOW()
                WHERE 
                    phone_number = '{$escapedDid}' 
                    OR phone_number = '+{$escapedDid}' 
                    OR phone_number = '00{$escapedDid}'
                    OR REPLACE(REPLACE(phone_number, '+', ''), ' ', '') = '{$escapedDid}'
                    OR phone_number LIKE '%{$escapedDid}%'
                    OR phone_number LIKE '%{$escapedLast8}%'";

            @$db->query($updateCallLogQuery);

            // D. Update status to PASS and update source_ip in bulk_dids table (Bulk Test Tab)
            $updateBulkDidQuery = "UPDATE bulk_dids 
                SET 
                    status = 'pass',
                    source_ip = CASE 
                        WHEN '{$escapedTrunk}' != '' AND '{$escapedTrunk}' != 'Asterisk-Inbound' THEN '{$escapedTrunk}'
                        WHEN '{$escapedIp}' != '' THEN '{$escapedIp}'
                        WHEN source_ip IS NULL OR source_ip = '' OR source_ip = '—' THEN '{$escapedTrunk}'
                        ELSE source_ip
                    END,
                    last_tested_at = NOW(),
                    updated_at = NOW()
                WHERE 
                    phone_number = '{$escapedDid}' 
                    OR phone_number = '+{$escapedDid}' 
                    OR phone_number = '00{$escapedDid}'
                    OR REPLACE(REPLACE(phone_number, '+', ''), ' ', '') = '{$escapedDid}'
                    OR phone_number LIKE '%{$escapedDid}%'
                    OR phone_number LIKE '%{$escapedLast8}%'";

            @$db->query($updateBulkDidQuery);

            // E. Pass Channel Variables back to Asterisk Dialplan
            if ($isRouted) {
                // Set every variable Asterisk dialplan could evaluate for allow branch
                setAgiChannelVar("WHITELIST_STATUS", "ALLOW");
                setAgiChannelVar("WHITELIST", "ALLOW");
                setAgiChannelVar("WHITELIST", "1");
                setAgiChannelVar("ROUTE_STATUS", "ALLOW");
                setAgiChannelVar("STATUS", "ALLOW");
                setAgiChannelVar("ALLOW", "1");
                setAgiChannelVar("IS_ROUTED", "1");
                setAgiChannelVar("IS_WHITELISTED", "1");
                setAgiChannelVar("WHITELISTED", "1");
                setAgiChannelVar("WHITELIST_RESULT", "ALLOW");
                setAgiChannelVar("ROUTE_DESTINATION", $routeDestination);
                setAgiChannelVar("DIAL_DESTINATION", $routeDestination);
                setAgiChannelVar("ROUTE", $routeDestination);
                setAgiChannelVar("DESTINATION", $routeDestination);
                setAgiChannelVar("ROUTE_EXT", $routeDestination);
                setAgiChannelVar("DIAL_EXTEN", $routeDestination);
                setAgiChannelVar("TARGET_EXTEN", $routeDestination);
            } else {
                setAgiChannelVar("WHITELIST_STATUS", "REJECT");
                setAgiChannelVar("WHITELIST", "REJECT");
                setAgiChannelVar("WHITELIST", "0");
                setAgiChannelVar("ALLOW", "0");
                setAgiChannelVar("IS_ROUTED", "0");
                setAgiChannelVar("IS_WHITELISTED", "0");
                setAgiChannelVar("STATUS", "REJECT");
                setAgiChannelVar("ROUTE_STATUS", "REJECT");
                setAgiChannelVar("WHITELIST_RESULT", "REJECT");
            }

            @$db->close();
        }
    } catch (\Throwable $e) {
        // Fail-safe: continue execution
    }
}

// 7. Return 0 to Asterisk dialplan
exit(0);
