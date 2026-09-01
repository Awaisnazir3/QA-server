#!/usr/bin/php -q
<?php
/**
 * Asterisk AGI - Whitelist Checker & Real-Time Abuse DID Detector
 * Location on Asterisk Server: /var/lib/asterisk/agi-bin/check_whitelist.php
 * Permissions: chmod +x /var/lib/asterisk/agi-bin/check_whitelist.php
 */

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
        $agi[trim($key)] = trim($val);
    }
}

// Extract DID from AGI arguments ($argv[1]) or agi_extension / agi_dnid
$didNumber = '';
if (isset($argv[1]) && !empty(trim($argv[1]))) {
    $didNumber = trim($argv[1]);
} elseif (isset($agi['agi_extension']) && !empty($agi['agi_extension'])) {
    $didNumber = $agi['agi_extension'];
} elseif (isset($agi['agi_dnid']) && !empty($agi['agi_dnid'])) {
    $didNumber = $agi['agi_dnid'];
}

$cleanDid = preg_replace('/[^0-9]/', '', $didNumber);

// Extract Trunk name from channel (e.g. PJSIP/eu3.didx.net-00000711 -> eu3.didx.net)
$trunkName = 'Asterisk-Inbound';
$channel = $agi['agi_channel'] ?? ($_SERVER['agi_channel'] ?? '');

if (preg_match('/(?:PJSIP|SIP)\/([a-zA-Z0-9\.\-_]+?)(?:-[0-9a-fA-F]+|\/|:|"|\s|$)/i', $channel, $m)) {
    $trunkName = $m[1];
} elseif (isset($argv[2]) && !empty(trim($argv[2]))) {
    $trunkName = trim($argv[2]);
}

$callId = $agi['agi_uniqueid'] ?? ($_SERVER['agi_uniqueid'] ?? null);

// 1. REAL-TIME LOGGING TO ABUSE DIDS TABLE
if (!empty($cleanDid) && strlen($cleanDid) >= 4) {
    try {
        $db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if (!$db->connect_error) {
            $escapedDid = $db->real_escape_string($cleanDid);
            $escapedTrunk = $db->real_escape_string($trunkName);
            $escapedCallId = $callId ? "'" . $db->real_escape_string($callId) . "'" : "NULL";

            $query = "INSERT INTO abuse_dids 
                (phone_number, source_trunk, hits_count, status, first_hit_at, last_hit_at, last_call_id, created_at, updated_at) 
                VALUES ('{$escapedDid}', '{$escapedTrunk}', 1, 'rejected', NOW(), NOW(), {$escapedCallId}, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    hits_count = hits_count + 1, 
                    last_hit_at = NOW(), 
                    source_trunk = IF(VALUES(source_trunk) != '', VALUES(source_trunk), source_trunk),
                    last_call_id = VALUES(last_call_id),
                    updated_at = NOW()";

            $db->query($query);
            $db->close();
        }
    } catch (\Throwable $e) {
        // Fail-safe: continue execution
    }
}

// 2. Return 0 to Asterisk dialplan
exit(0);
