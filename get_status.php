<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: application/json');

$db_host = "165.227.88.28";
$db_user = "admin";
$db_pass = "12343211";
$db_name = "telecom_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(array());
    exit;
}

$result = $conn->query("SELECT id, status FROM call_logs");
$statuses = array();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status_clean = !empty($row['status']) ? strtolower(trim($row['status'])) : 'pending';
        if ($status_clean !== 'pass' && $status_clean !== 'fail' && $status_clean !== 'route') { $status_clean = 'pending'; }
        $statuses[$row['id']] = $status_clean;
    }
}

// Include active call count so JS can blink the hangup button
$active_calls = 0;
$ch_raw = @shell_exec("sudo /usr/sbin/asterisk -rx 'core show channels' 2>/dev/null");
if ($ch_raw && preg_match('/([0-9]+)\s+active\s+call/i', $ch_raw, $m)) {
    $active_calls = intval($m[1]);
}
$statuses['_active_calls'] = $active_calls;

echo json_encode($statuses);
$conn->close();
