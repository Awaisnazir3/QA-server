#!/usr/bin/php -q
<?php
/**
 * Asterisk AGI - Whitelist Checker & Real-Time Abuse DID Detector
 * Location on Asterisk Server: /var/lib/asterisk/agi-bin/check_whitelist.php
 * Permissions: chmod +x /var/lib/asterisk/agi-bin/check_whitelist.php
 */

// Guarantee that caller hangup does NOT terminate script before writing to database
@ignore_user_abort(true);
@set_time_limit(5);

// Database Configuration
$dbHost = '127.0.0.1';
$dbUser = 'admin';
$dbPass = '12343211';
$dbName = 'telecom_db';

// Read AGI Environment variables
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

// Extract DID from AGI arguments ($argv[1]) or agi_extension / agi_dnid / agi_callerid
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

// Extract Channel name from all available sources
$channel = $agi['agi_channel'] ?? ($_SERVER['agi_channel'] ?? ($_ENV['agi_channel'] ?? (getenv('agi_channel') ?: '')));

// If channel not in initial AGI headers, query Asterisk directly via AGI protocol
if (empty($channel) || $channel === 'Asterisk-Inbound') {
    @fputs(STDOUT, "GET VARIABLE CHANNEL\n");
    @fflush(STDOUT);
    $resp = @fgets(STDIN);
    if ($resp && preg_match('/\((.+?)\)/', $resp, $rm) && !empty(trim($rm[1]))) {
        $channel = trim($rm[1]);
    }
}

if (empty($channel) || $channel === 'Asterisk-Inbound') {
    @fputs(STDOUT, "GET VARIABLE PJSIP_ENDPOINT\n");
    @fflush(STDOUT);
    $resp = @fgets(STDIN);
    if ($resp && preg_match('/\((.+?)\)/', $resp, $rm) && !empty(trim($rm[1]))) {
        $channel = trim($rm[1]);
    }
}

$trunkName = 'Asterisk-Inbound';
$knownTrunks = [
    'sip10.didx.net', 'eu2.didx.net', 'eu3.didx.net', 'ca.didx.net', 
    'us2.didx.net', 'Sip.belloceanic.com', 'sip.belloceanic.com'
];

$allInputs = implode(' ', [
    $channel,
    $argv[2] ?? '',
    $argv[1] ?? '',
    $agi['agi_channel'] ?? '',
    $agi['agi_request'] ?? '',
    json_encode($agi)
]);

foreach ($knownTrunks as $kt) {
    if (stripos($allInputs, $kt) !== false) {
        $trunkName = $kt;
        break;
    }
}

if ($trunkName === 'Asterisk-Inbound') {
    if (preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s|$)/i', $allInputs, $m)) {
        $trunkName = $m[1];
    } elseif (isset($argv[2]) && !empty(trim($argv[2]))) {
        $trunkName = trim($argv[2]);
    }
}

// Fallback: search Asterisk log messages if running locally on server
if ($trunkName === 'Asterisk-Inbound' && !empty($cleanDid) && PHP_OS_FAMILY !== 'Windows') {
    $escaped = escapeshellarg($cleanDid);
    $grepOut = @shell_exec("grep -F {$escaped} /var/log/asterisk/messages 2>/dev/null | tail -n 10");
    if ($grepOut) {
        foreach ($knownTrunks as $kt) {
            if (stripos($grepOut, $kt) !== false) {
                $trunkName = $kt;
                break;
            }
        }
        if ($trunkName === 'Asterisk-Inbound' && preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s)/i', $grepOut, $gm)) {
            $trunkName = $gm[1];
        }
    }
}

// Resolve Source IP
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
if (isset($trunkIpMap[$trunkName])) {
    $sourceIp = $trunkIpMap[$trunkName];
} elseif (filter_var($trunkName, FILTER_VALIDATE_IP)) {
    $sourceIp = $trunkName;
} elseif ($trunkName !== 'Asterisk-Inbound') {
    $resolved = @gethostbyname($trunkName);
    if ($resolved && $resolved !== $trunkName && filter_var($resolved, FILTER_VALIDATE_IP)) {
        $sourceIp = $resolved;
    }
}

$callId = $agi['agi_uniqueid'] ?? ($_SERVER['agi_uniqueid'] ?? null);

// 1. REAL-TIME LOGGING TO ABUSE DIDS TABLE
if (!empty($cleanDid) && strlen($cleanDid) >= 2) {
    try {
        $db = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($db && !$db->connect_error) {
            $escapedDid = $db->real_escape_string($cleanDid);
            $escapedTrunk = $db->real_escape_string($trunkName);
            $escapedIp = $db->real_escape_string($sourceIp);
            $escapedCallId = $callId ? "'" . $db->real_escape_string($callId) . "'" : "NULL";

            // Atomic Insert / Increment on duplicate phone_number with updated source trunk/IP on last hit
            $query = "INSERT INTO abuse_dids 
                (phone_number, source_trunk, source_ip, hits_count, status, first_hit_at, last_hit_at, last_call_id, created_at, updated_at) 
                VALUES ('{$escapedDid}', '{$escapedTrunk}', '{$escapedIp}', 1, 'rejected', NOW(), NOW(), {$escapedCallId}, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    hits_count = hits_count + 1, 
                    last_hit_at = NOW(), 
                    source_trunk = IF(VALUES(source_trunk) != '' AND VALUES(source_trunk) != 'Asterisk-Inbound', VALUES(source_trunk), source_trunk),
                    source_ip = IF(VALUES(source_ip) != '', VALUES(source_ip), source_ip),
                    last_call_id = IF(VALUES(last_call_id) IS NOT NULL, VALUES(last_call_id), last_call_id),
                    updated_at = NOW()";

            @$db->query($query);
            @$db->close();
        }
    } catch (\Throwable $e) {
        // Fail-safe: continue execution
    }
}

// 2. Return 0 to Asterisk dialplan
exit(0);
