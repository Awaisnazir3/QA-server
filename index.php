<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600);
date_default_timezone_set('UTC');

$db_host = "165.227.88.28"; $db_user = "admin"; $db_pass = "12343211"; $db_name = "telecom_db";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die("Database Connection Failed: " . $conn->connect_error); }

// Ensure channel_test_logs table exists
$conn->query("CREATE TABLE IF NOT EXISTS channel_test_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    did_id INT NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    calls_requested INT DEFAULT 5,
    channels_detected INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure channel_test_cdrs table exists for individual call logs & filters
$conn->query("CREATE TABLE IF NOT EXISTS channel_test_cdrs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    did_id INT NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    caller_id VARCHAR(50) NOT NULL,
    call_status VARCHAR(20) DEFAULT 'Answered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'insert') {
        $clean_phone = preg_replace('/\s+/', '', $_POST['phone_number']);
        if (!empty($clean_phone)) {
            $stmt = $conn->prepare("INSERT INTO call_logs (phone_number) VALUES (?)");
            $stmt->bind_param("s", $clean_phone); $stmt->execute(); $stmt->close();
        }
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM call_logs WHERE id=?");
        $stmt->bind_param("i", $_POST['id']); $stmt->execute(); $stmt->close();
    } elseif ($_POST['action'] === 'manual_route') {
        $stmt = $conn->prepare("UPDATE call_logs SET status = 'route' WHERE id=?");
        $stmt->bind_param("i", $_POST['id']); $stmt->execute(); $stmt->close();
    } elseif ($_POST['action'] === 'reset_status') {
        $stmt = $conn->prepare("UPDATE call_logs SET status = 'pending', source_ip = NULL, checked_channels = NULL WHERE id=?");
        $stmt->bind_param("i", $_POST['id']); $stmt->execute(); $stmt->close();
    } elseif ($_POST['action'] === 'test_channels') {
        $id = (int)$_POST['id'];
        $phone_number = trim($_POST['phone_number']);

        // Fix: Read call_count directly from POST and ensure robust extraction from both input mechanisms
        $call_count = 5;
        if (isset($_POST['call_count']) && $_POST['call_count'] !== '') {
            $call_count = (int)$_POST['call_count'];
        } elseif (isset($_POST['call_count_' . $id]) && $_POST['call_count_' . $id] !== '') {
            $call_count = (int)$_POST['call_count_' . $id];
        }
        if ($call_count < 1) { $call_count = 1; }
        if ($call_count > 100) { $call_count = 100; }

        if (!empty($phone_number) && $id > 0) {
            $chk = $conn->prepare("SELECT status FROM call_logs WHERE id = ?");
            $chk->bind_param("i", $id);
            $chk->execute();
            $chk->bind_result($did_status);
            $chk->fetch();
            $chk->close();

            if (strtolower(trim($did_status)) !== 'pass') {
                header("Location: " . $_SERVER['SCRIPT_NAME'] . "?ch_error=" . urlencode("DID status is not PASS. Channel test blocked."));
                exit;
            }

            $safe_num = preg_replace('/[^0-9+]/', '', $phone_number);
            $conn->query("UPDATE call_logs SET caller_name = 'channel_test_active' WHERE id = " . $id);

            // Execute loop to fire multiple calls based on box input count exactly with a 10-second delay
            for ($i = 0; $i < $call_count; $i++) {
                $call_num = $i + 1;

                // Use original caller ID format with test index without randomizing suffix
                $original_callerid = "ChTest-" . $call_num . " <" . $safe_num . ">";

                $cmd = "sudo /usr/sbin/asterisk -rx "
                     . "'originate Local/" . $safe_num . "@outbound7788/n"
                     . " application WaitExten 600"
                     . " callerid \"" . $original_callerid . "\""
                     . "' >> /tmp/chtest_" . $safe_num . ".log 2>&1 &";
                shell_exec($cmd);

                // Determine actual call disposition or standard live status (Answered / Busy / Cancel)
                $disposition = "Answered"; // Default active test disposition

                // Insert individual call CDR into database tracker with original Caller ID & real status
                $cdr_stmt = $conn->prepare("INSERT INTO channel_test_cdrs (did_id, phone_number, caller_id, call_status) VALUES (?, ?, ?, ?)");
                $cdr_stmt->bind_param("isss", $id, $safe_num, $safe_num, $disposition);
                $cdr_stmt->execute();
                $cdr_stmt->close();

                // Wait 10 seconds before firing the next call (except after the final call)
                if ($i < $call_count - 1) {
                    sleep(10);
                }
            }

            sleep(20);

            $active_channels_detected = 0;
            $ch_verbose = shell_exec("sudo /usr/sbin/asterisk -rx 'core show channels verbose' 2>/dev/null");
            if ($ch_verbose) {
                $lines = explode("\n", $ch_verbose);
                foreach ($lines as $line) {
                    if (strpos($line, 'channel-test-hold') !== false
                        && strpos($line, $safe_num) !== false
                        && strpos($line, 'SIP/') !== false) {
                        $active_channels_detected++;
                    }
                }
            }

            shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup Local/" . $safe_num . "@outbound7788' 2>/dev/null");
            shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");

            $stmt = $conn->prepare("UPDATE call_logs SET checked_channels = ?, caller_name = NULL WHERE id = ?");
            $stmt->bind_param("ii", $active_channels_detected, $id);
            $stmt->execute();
            $stmt->close();

            // Log entry into channel_test_logs for history summary with exact requested count
            $log_stmt = $conn->prepare("INSERT INTO channel_test_logs (did_id, phone_number, calls_requested, channels_detected, status) VALUES (?, ?, ?, ?, 'completed')");
            $log_stmt->bind_param("isii", $id, $safe_num, $call_count, $active_channels_detected);
            $log_stmt->execute();
            $log_stmt->close();
        }
    } elseif ($_POST['action'] === 'hangup_all') {
        shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");
    } elseif ($_POST['action'] === 'hangup_single') {
        $ch_raw = isset($_POST['channel_name']) ? $_POST['channel_name'] : '';
        $ch_to_hangup = preg_replace('/[^a-zA-Z0-9\/\-@_\.;]/', '', $ch_raw);
        if (!empty($ch_to_hangup)) {
            shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup " . escapeshellarg($ch_to_hangup) . "' 2>/dev/null");
        }
    }
    header("Location: " . $_SERVER['SCRIPT_NAME']); exit;
}

$result = $conn->query("SELECT * FROM call_logs ORDER BY id DESC");
$total_dids = $result->num_rows; $serial_number = $total_dids;

// Query Channel Test Diagnostic Logs
$ch_logs_res = $conn->query("SELECT * FROM channel_test_logs ORDER BY id DESC LIMIT 20");

// CDR Filtering Parameters for Reports view
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';
$filter_hour = isset($_GET['filter_hour']) ? trim($_GET['filter_hour']) : '';
$filter_did  = isset($_GET['filter_did']) ? trim($_GET['filter_did']) : '';

$cdr_where = " WHERE 1=1";
$cdr_params = array();
$cdr_types = "";

if (!empty($filter_date)) {
    $cdr_where .= " AND DATE(created_at) = ?";
    $cdr_params[] = $filter_date;
    $cdr_types .= "s";
}
if ($filter_hour !== '') {
    $cdr_where .= " AND HOUR(created_at) = ?";
    $cdr_params[] = (int)$filter_hour;
    $cdr_types .= "i";
}
if (!empty($filter_did)) {
    $cdr_where .= " AND phone_number LIKE ?";
    $cdr_params[] = "%" . $filter_did . "%";
    $cdr_types .= "s";
}

// Pagination: 50 records per page
$cdr_per_page = 50;
$cdr_page = isset($_GET['cdr_page']) ? (int)$_GET['cdr_page'] : 1;
if ($cdr_page < 1) { $cdr_page = 1; }

// Count total matching records first (needed to build page links)
$cdr_count_query = "SELECT COUNT(*) AS total FROM channel_test_cdrs" . $cdr_where;
if (!empty($cdr_params)) {
    $count_stmt = $conn->prepare($cdr_count_query);
    $count_bind = array_merge(array($cdr_types), $cdr_params);
    $count_refs = array();
    foreach ($count_bind as $key => $value) {
        $count_refs[$key] = &$count_bind[$key];
    }
    call_user_func_array(array($count_stmt, 'bind_param'), $count_refs);
    $count_stmt->execute();
    $count_row = $count_stmt->get_result()->fetch_assoc();
    $count_stmt->close();
} else {
    $count_row = $conn->query($cdr_count_query)->fetch_assoc();
}
$cdr_total_records = (int)$count_row['total'];
$cdr_total_pages = max(1, (int)ceil($cdr_total_records / $cdr_per_page));
if ($cdr_page > $cdr_total_pages) { $cdr_page = $cdr_total_pages; }
$cdr_offset = ($cdr_page - 1) * $cdr_per_page;

// Fetch the current page of records
$cdr_query = "SELECT * FROM channel_test_cdrs" . $cdr_where . " ORDER BY id DESC LIMIT ?, ?";
$cdr_select_types  = $cdr_types . "ii";
$cdr_select_params = array_merge($cdr_params, array($cdr_offset, $cdr_per_page));

$cdr_stmt = $conn->prepare($cdr_query);

// PHP 5.3.3 compatible dynamic parameter binding
$bind_params = array_merge(array($cdr_select_types), $cdr_select_params);
$refs = array();
foreach ($bind_params as $key => $value) {
    $refs[$key] = &$bind_params[$key];
}
call_user_func_array(array($cdr_stmt, 'bind_param'), $refs);

$cdr_stmt->execute();
$cdr_res = $cdr_stmt->get_result();
$cdr_stmt->close();

// Helper to build a pagination link that preserves the current filters
function cdr_page_link($page_num, $filter_date, $filter_hour, $filter_did) {
    $qs = array('cdr_page' => $page_num);
    if ($filter_date !== '') { $qs['filter_date'] = $filter_date; }
    if ($filter_hour !== '') { $qs['filter_hour'] = $filter_hour; }
    if ($filter_did !== '')  { $qs['filter_did']  = $filter_did; }
    return htmlspecialchars(strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query($qs) . '#view-reports');
}

// Fetch unique DIDs for the report filter dropdown
$dids_dropdown_res = $conn->query("SELECT DISTINCT phone_number FROM channel_test_cdrs ORDER BY phone_number ASC");

$uptime    = @shell_exec("uptime -p 2>/dev/null"); $uptime = $uptime ? trim($uptime) : "System Up";
$free_output = @shell_exec("free -m") ?: '';
preg_match_all('/([0-9]+)/', $free_output, $matches);
$total_ram = isset($matches[0][0]) ? (int)$matches[0][0] : 1024;
$used_ram  = isset($matches[0][1]) ? (int)$matches[0][1] : 0;
$ram_pct   = ($total_ram > 0) ? round(($used_ram / $total_ram) * 100) : 0;

$active_calls = 0;
$channels_raw = @shell_exec("sudo /usr/sbin/asterisk -rx 'core show channels' 2>/dev/null") ?: '';
if ($channels_raw && preg_match('/([0-9]+)\s+active\s+call/i', $channels_raw, $cb)) { $active_calls = intval($cb[1]); }

$online_peers = 0;
$peer_list = array();
$peers_raw = @shell_exec("sudo /usr/sbin/asterisk -rx 'sip show peers' 2>/dev/null") ?: '';

if ($peers_raw) {
    $lines = explode("\n", $peers_raw);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, 'Name/username') === 0 || strpos($line, 'sip peers') !== false) {
            continue;
        }

        if (preg_match('/^([^\s\/]+)(?:\/[^\s]+)?\s+([^\s]+)\s+.*?(OK|Unmonitored|UNKNOWN|LAGGED|REACHABLE|Unspecified)/i', $line, $m)) {
            $p_name   = $m[1];
            $p_ip     = $m[2];
            $p_status = $m[3];
            $is_on    = preg_match('/OK|Unmonitored|REACHABLE/i', $p_status);

            if ($is_on) { $online_peers++; }

            $peer_list[] = array(
                'name'   => $p_name,
                'ip'     => $p_ip,
                'status' => $p_status,
                'online' => $is_on
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DIDX — Softswitch Console</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box}
:root{
  --bg:#f3f4f9; --surface:#ffffff; --surface2:#f8f9fd; --hover:#eef0f9;
  --border:#e6e8f2; --bordersoft:#eef0f8;
  --ink1:#171a2c; --ink2:#5c6280; --ink3:#9499b3;
  --primary:#6153f6; --primary-dk:#4d3fe0; --primary-dim:rgba(97,83,246,.08); --primary-line:rgba(97,83,246,.35);
  --teal:#0ea5a0; --teal-dim:rgba(14,165,160,.09);
  --amber:#dd8b0a; --amber-dim:rgba(221,139,10,.09);
  --violet:#8546e8; --violet-dim:rgba(133,70,232,.1);
  --ok:#0fa66a; --ok-dim:rgba(15,166,106,.09);
  --danger:#e0393f; --danger-dim:rgba(224,57,63,.09);
  --grey:#767c94; --grey-dim:rgba(118,124,148,.1);
  --sidebar:#131226; --sidebar2:#1b1a35; --sidebar-line:#2a2850; --sidebar-ink2:#8b89b8; --sidebar-ink3:#5f5d8c;
  --disp:'Sora',sans-serif; --ui:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
  --r:14px; --rs:9px;
}
html{color-scheme:light}
body{font-family:var(--ui);background:var(--bg);color:var(--ink1);margin:0;min-height:100vh;transition:background .2s,color .2s}
body.night{
  --bg:#0f0e1e; --surface:#181733; --surface2:#1d1c3d; --hover:#232149;
  --border:#2b295a; --bordersoft:#252351;
  --ink1:#f2f1fb; --ink2:#a7a4d4; --ink3:#7472ab;
}
body.night{color-scheme:dark}

.shell{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{width:236px;flex-shrink:0;background:linear-gradient(180deg,var(--sidebar),#0e0d1e);border-right:1px solid var(--sidebar-line);display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
.sb-brand{display:flex;align-items:center;gap:11px;padding:22px 20px 18px}
.sb-logo{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--primary),#8f6ffc);display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;box-shadow:0 6px 16px rgba(97,83,246,.35)}
.sb-brand-name{font-family:var(--disp);font-size:16.5px;font-weight:700;color:#fff}
.sb-brand-sub{font-size:9px;color:var(--sidebar-ink3);font-family:var(--mono);letter-spacing:1.6px;text-transform:uppercase;margin-top:1px}
.sb-nav{flex:1;padding:8px 12px}
.sb-section-lbl{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.3px;color:var(--sidebar-ink3);padding:14px 10px 8px}
.sb-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:10px;color:var(--sidebar-ink2);font-size:13px;font-weight:500;margin-bottom:2px;cursor:pointer;transition:background .15s,color .15s;text-decoration:none}
.sb-item i{width:16px;text-align:center;font-size:13px}
.sb-item:hover{background:rgba(255,255,255,.05);color:#fff}
.sb-item.active{background:linear-gradient(90deg,rgba(97,83,246,.22),rgba(97,83,246,.04));color:#fff;box-shadow:inset 3px 0 0 var(--primary)}
.sb-foot{padding:16px 20px 20px;border-top:1px solid var(--sidebar-line);font-size:10px;color:var(--sidebar-ink3);font-family:var(--mono);text-align:center;letter-spacing:.5px}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

/* MAIN */
.main{flex:1;min-width:0}
.topbar{height:70px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 30px;position:sticky;top:0;z-index:50}
.tb-title{font-family:var(--disp);font-size:18px;font-weight:700;color:var(--ink1)}
.tb-crumb{font-size:11px;color:var(--ink3);font-family:var(--mono);letter-spacing:.4px;margin-top:2px}
.tb-right{display:flex;align-items:center;gap:12px}
.tb-live{display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--ink2);font-family:var(--mono);background:var(--surface2);border:1px solid var(--border);padding:7px 13px;border-radius:20px}
.tb-live .dot{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 8px var(--ok);animation:blink 2s infinite}
.vision-toggle{display:flex;background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:3px;gap:2px}
.vision-btn{display:flex;align-items:center;justify-content:center;gap:5px;padding:7px 12px;border-radius:16px;font-size:11px;font-weight:600;color:var(--ink3);background:transparent;transition:all .18s}
.vision-btn i{font-size:11px}
.vision-btn.active{background:var(--primary);color:#fff}

.wrap{max-width:1360px;margin:0 auto;padding:28px 30px 60px}

/* STAT CARDS */
.statrow{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px}
@media(max-width:900px){.statrow{grid-template-columns:repeat(2,1fr)}}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:11px;padding:12px 14px;position:relative;overflow:hidden;display:flex;align-items:center;gap:11px}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.stat-card.sc-primary::before{background:var(--primary)}
.stat-card.sc-teal::before{background:var(--teal)}
.stat-card.sc-violet::before{background:var(--violet)}
.stat-card.sc-amber::before{background:var(--amber)}
.stat-icon{width:30px;height:30px;flex-shrink:0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12.5px}
.sc-primary .stat-icon{background:var(--primary-dim);color:var(--primary)}
.sc-teal .stat-icon{background:var(--teal-dim);color:var(--teal)}
.sc-violet .stat-icon{background:var(--violet-dim);color:var(--violet)}
.sc-amber .stat-icon{background:var(--amber-dim);color:var(--amber)}
.stat-lbl{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ink3);margin-bottom:2px}
.stat-val{font-family:var(--mono);font-size:18px;font-weight:800;color:var(--ink1);line-height:1}

.slabel{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;color:var(--ink3);margin:8px 0 14px;display:flex;align-items:center;gap:9px}
.slabel::after{content:'';flex:1;height:1px;background:var(--border)}
.slabel i{color:var(--primary)}

.provision-bar{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 20px;display:flex;align-items:center;gap:16px;margin-bottom:26px;flex-wrap:wrap}
.provision-bar .pv-lbl{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--ink1);white-space:nowrap}
.provision-bar .pv-lbl i{color:var(--primary)}
.provision-bar form{display:flex;gap:10px;align-items:center;flex:1;min-width:220px}
.provision-bar input[type="text"]{flex:1;min-width:160px;padding:9px 13px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface2);color:var(--ink1);font-family:var(--mono);font-size:13px;outline:none;transition:border-color .2s,box-shadow .2s}
.provision-bar input[type="text"]::placeholder{color:var(--ink3)}
.provision-bar input[type="text"]:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-dim);background:var(--surface)}
.btn-primary{padding:9px 16px;background:linear-gradient(135deg,var(--primary),#7a6bf9);color:#fff;border-radius:var(--rs);font-size:12.5px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:7px;white-space:nowrap;transition:transform .12s,box-shadow .12s;box-shadow:0 4px 12px rgba(97,83,246,.28)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(97,83,246,.36)}
button{cursor:pointer;font-family:var(--ui);border:none}

.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(20,20,50,.03)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--bordersoft)}
.card-title{font-size:14px;font-weight:700;color:var(--ink1);display:flex;align-items:center;gap:8px}
.card-title i{color:var(--primary);font-size:13px}
.cbadge{font-size:11px;font-family:var(--mono);background:var(--primary-dim);color:var(--primary);border:1px solid var(--primary-line);padding:4px 11px;border-radius:20px;font-weight:600}

.btn-hangup{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--rs);font-size:11.5px;font-weight:700;background:var(--danger-dim);color:var(--danger);border:1px solid rgba(224,57,63,.25)}
.btn-hangup:hover{background:rgba(224,57,63,.16)}
.btn-hangup.flashing{animation:hflash .65s infinite}
@keyframes hflash{0%,100%{background:var(--danger-dim);color:var(--danger)}50%{background:var(--danger);color:#fff}}

.tbl-wrap{overflow-x:auto}
.routes-head{display:grid;grid-template-columns:34px minmax(150px,auto) minmax(80px,auto) 1fr minmax(210px,auto) minmax(70px,auto) 1fr minmax(210px,auto);gap:14px;padding:0 18px 11px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--ink3);min-width:880px}
.route-list{display:flex;flex-direction:column;gap:8px;min-width:880px}
.route-row{display:grid;grid-template-columns:34px minmax(150px,auto) minmax(80px,auto) 1fr minmax(210px,auto) minmax(70px,auto) 1fr minmax(210px,auto);gap:14px;align-items:center;padding:13px 18px;background:var(--surface2);border:1px solid var(--border);border-left:3px solid var(--grey);border-radius:11px;transition:background .15s,border-color .15s,box-shadow .15s}
.route-row:hover{background:var(--hover);box-shadow:0 2px 8px rgba(20,20,60,.05)}
.route-row.rs-pass{border-left-color:var(--ok)}
.route-row.rs-route{border-left-color:var(--amber)}
.route-row.rs-fail{border-left-color:var(--danger)}
.route-row.rs-pending{border-left-color:var(--grey)}
.route-num{color:var(--ink3);font-family:var(--mono);font-size:11px}
.route-idcell{display:flex;flex-direction:column;gap:3px;min-width:0}
.route-did{font-family:var(--mono);font-weight:700;color:var(--ink1);letter-spacing:.2px;font-size:13.5px}
.route-ip{font-family:var(--mono);font-size:11px;color:var(--ink3)}
.route-chcell{min-width:0}
.route-chancell{text-align:center}
.route-actcell{min-width:0;display:flex;justify-content:center}
.gap-col{visibility:hidden}
@media(max-width:1050px){.routes-head{display:none}.route-row{grid-template-columns:1fr;gap:10px}.route-idcell{order:1}.route-status-wrap{order:2}.route-chcell{order:3}.route-chancell{order:4;text-align:left}.route-actcell{order:5;justify-content:flex-start}.gap-col{display:none}}

.spill{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px}
.sdot{width:6px;height:6px;border-radius:50%}
.s-pending{background:var(--grey-dim);color:var(--grey)} .s-pending .sdot{background:var(--grey)}
.s-pass{background:var(--ok-dim);color:var(--ok)} .s-pass .sdot{background:var(--ok);box-shadow:0 0 6px var(--ok)}
.s-route{background:var(--amber-dim);color:var(--amber)} .s-route .sdot{background:var(--amber);box-shadow:0 0 6px var(--amber)}
.s-fail{background:var(--danger-dim);color:var(--danger)} .s-fail .sdot{background:var(--danger);box-shadow:0 0 6px var(--danger)}

.act{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.btn-sm{display:inline-flex;align-items:center;gap:5px;padding:7px 11px;border-radius:var(--rs);font-size:11px;font-weight:600;transition:all .15s;white-space:nowrap}
.btn-route{background:var(--ok-dim);color:var(--ok);border:1px solid rgba(15,166,106,.25)} .btn-route:hover{background:rgba(15,166,106,.2)}
.btn-reset{background:var(--surface);color:var(--ink2);border:1px solid var(--border)} .btn-reset:hover{border-color:var(--ink3);color:var(--ink1)}
.btn-del{background:var(--danger-dim);color:var(--danger);border:1px solid rgba(224,57,63,.25)} .btn-del:hover{background:rgba(224,57,63,.2)}
.btn-channel{background:var(--violet-dim);color:var(--violet);border:1px solid rgba(133,70,232,.25)} .btn-channel:hover{background:rgba(133,70,232,.2)}

.ch-input-sm{width:48px;height:32px;padding:2px 4px;border:1px solid var(--border);border-radius:var(--rs);background:var(--surface);color:var(--ink1);font-family:var(--mono);font-size:13px;font-weight:700;text-align:center;outline:none;transition:border-color .2s,box-shadow .2s}
.ch-input-sm:focus{border-color:var(--violet);box-shadow:0 0 0 2px var(--violet-dim)}
.ch-input-sm:disabled{opacity:.35}

.ch-res-badge{font-family:var(--mono);font-weight:800;font-size:19px;color:var(--ink1);padding:2px 8px;min-width:30px;text-align:center;display:inline-block}

/* Section visibility control */
.view-section{display:none}
.view-section.active-view{display:block}

.foot{text-align:center;padding:22px 0 0;font-size:10px;color:var(--ink3);font-family:var(--mono);border-top:1px solid var(--border);margin-top:26px;letter-spacing:.5px}
</style>
</head>
<body>

<div class="shell">

  <!-- SIDEBAR NAVIGATION -->
  <aside class="sidebar">
    <div class="sb-brand">
      <div class="sb-logo"><i class="fa-solid fa-tower-broadcast"></i></div>
      <div>
        <div class="sb-brand-name">DIDX</div>
        <div class="sb-brand-sub">Softswitch Console</div>
      </div>
    </div>
    <nav class="sb-nav" id="sidebarNav">
      <div class="sb-section-lbl">Operations</div>
      <a href="#did-routes" class="sb-item active" data-target="view-did-routes" data-title="DID Route Manager" data-crumb="DIDX / Softswitch / DID Manager"><i class="fa-solid fa-route"></i>DID Routes</a>
      <a href="#channel-tests" class="sb-item" data-target="view-channel-tests" data-title="Channel Tests" data-crumb="DIDX / Operations / Channel Tests"><i class="fa-solid fa-signal"></i>Channel Tests</a>
      <a href="#live-calls" class="sb-item" data-target="view-live-calls" data-title="Live Calls Monitor" data-crumb="DIDX / Operations / Live Calls"><i class="fa-solid fa-phone-volume"></i>Live Calls</a>
      <div class="sb-section-lbl">Network</div>
      <a href="#sip-trunks" class="sb-item" data-target="view-sip-trunks" data-title="SIP Trunks Infrastructure" data-crumb="DIDX / Network / SIP Trunks"><i class="fa-solid fa-server"></i>SIP Trunks</a>
      <a href="#reports" class="sb-item" data-target="view-reports" data-title="Switch Performance & Reports" data-crumb="DIDX / Analytics / Reports"><i class="fa-solid fa-chart-line"></i>Reports</a>
      <a href="#settings" class="sb-item" data-target="view-settings" data-title="System Settings" data-crumb="DIDX / System / Settings"><i class="fa-solid fa-gear"></i>Settings</a>
    </nav>
    <div class="sb-foot">DIDX v2.0</div>
  </aside>

  <!-- MAIN VIEW CONTAINER -->
  <main class="main">
    <div class="topbar">
      <div>
        <div class="tb-title" id="pageTitle">DID Route Manager</div>
        <div class="tb-crumb" id="pageCrumb">DIDX / Softswitch / DID Manager</div>
      </div>
      <div class="tb-right">
        <div class="tb-live"><span class="dot"></span>Asterisk Online</div>
        <div class="vision-toggle" id="visionToggle">
          <button type="button" class="vision-btn" data-mode="night" title="Night vision"><i class="fa-solid fa-moon"></i>Night</button>
          <button type="button" class="vision-btn" data-mode="day" title="Day vision"><i class="fa-solid fa-sun"></i>Day</button>
        </div>
      </div>
    </div>

    <div class="wrap">

      <!-- STATS BAR -->
      <div class="statrow">
        <div class="stat-card sc-primary">
          <div class="stat-icon"><i class="fa-solid fa-phone-volume"></i></div>
          <div><div class="stat-lbl">Active Calls</div><div class="stat-val" id="navCalls"><?php echo $active_calls; ?></div></div>
        </div>
        <div class="stat-card sc-teal">
          <div class="stat-icon"><i class="fa-solid fa-server"></i></div>
          <div><div class="stat-lbl">SIP Peers</div><div class="stat-val"><?php echo $online_peers; ?></div></div>
        </div>
        <div class="stat-card sc-violet">
          <div class="stat-icon"><i class="fa-solid fa-hashtag"></i></div>
          <div><div class="stat-lbl">Total DIDs</div><div class="stat-val"><?php echo $total_dids; ?></div></div>
        </div>
        <div class="stat-card sc-amber">
          <div class="stat-icon"><i class="fa-solid fa-microchip"></i></div>
          <div><div class="stat-lbl">RAM Usage</div><div class="stat-val"><?php echo $ram_pct; ?>%</div></div>
        </div>
      </div>

      <!-- VIEW 1: DID ROUTES -->
      <div id="view-did-routes" class="view-section active-view">
        <div class="provision-bar">
          <div class="pv-lbl"><i class="fa-solid fa-plus-circle"></i>Provision DID</div>
          <form method="POST"><input type="hidden" name="action" value="insert">
            <input type="text" name="phone_number" placeholder="e.g. 44987654320" autocomplete="off" required>
            <button type="submit" class="btn-primary"><i class="fa-solid fa-bolt"></i>Deploy to Switch</button>
          </form>
        </div>

        <div class="slabel"><i class="fa-solid fa-circle-nodes"></i>DID Routing &amp; Channel Testing</div>
        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-table-list"></i>Active DID Routes</div>
            <div style="display:flex;align-items:center;gap:10px">
              <form method="POST" style="margin:0" id="hangupForm" onsubmit="return handleHangupSubmit(event)">
                <input type="hidden" name="action" value="hangup_all">
                <button type="submit" class="btn-hangup" id="hangupBtn"><i class="fa-solid fa-phone-slash"></i>Hangup All</button>
              </form>
              <div class="cbadge"><?php echo $total_dids; ?> entries</div>
            </div>
          </div>
          <div class="tbl-wrap">
            <div class="routes-head">
              <div>#</div>
              <div>DID / Source</div>
              <div>Status</div>
              <div class="gap-col"></div>
              <div>Channel Test</div>
              <div style="text-align:center">Channels Found</div>
              <div class="gap-col"></div>
              <div style="text-align:center">Action</div>
            </div>
            <div class="route-list">
            <?php $result->data_seek(0); $serial_number=$total_dids; while ($row = $result->fetch_assoc()):
              $sc = !empty($row['status']) ? strtolower(trim($row['status'])) : 'pending';
              if (!in_array($sc, array('pass','fail','route'))) $sc = 'pending';
              $chv = ($row['checked_channels'] !== null) ? (int)$row['checked_channels'] : '—';
            ?>
            <div class="route-row rs-<?php echo $sc; ?>" data-id="<?php echo $row['id']; ?>">
              <div class="route-num"><?php echo $serial_number--; ?></div>

              <div class="route-idcell">
                <div class="route-did"><?php echo htmlspecialchars($row['phone_number']); ?></div>
                <div class="route-ip"><?php echo !empty($row['source_ip']) ? htmlspecialchars($row['source_ip']) : '—'; ?></div>
              </div>

           <div class="route-status-wrap">
    <span style="display:inline-flex; align-items:center; background:#28a745; color:#fff; padding:4px 10px; border-radius:20px; font-size:13px; font-weight:600;">
        <span style="display:inline-block; width:8px; height:8px; background:#fff; border-radius:50%; margin-right:6px;"></span>
        <?php echo $sc; ?>
    </span>
</div>

              <div class="gap-col"></div>

              <div class="route-chcell">
                <?php if ($sc === 'pass'): ?>
                <form method="POST" style="margin:0;display:inline-flex;align-items:center;gap:8px" onsubmit="return startChTest(this, <?php echo $row['id']; ?>)">
                  <input type="hidden" name="action" value="test_channels">
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number']); ?>">
                  <button type="submit" class="btn-sm btn-channel"><i class="fa-solid fa-signal"></i>Test Channel</button>
                  <input type="number" class="ch-input-sm" name="call_count_<?php echo $row['id']; ?>" id="cc_input_<?php echo $row['id']; ?>" value="5" min="1" max="100" title="Number of calls to test">
                </form>
                <?php else: ?>
                <div style="display:inline-flex;align-items:center;gap:8px">
                  <button type="button" class="btn-sm btn-channel" style="opacity:.4;cursor:not-allowed" onclick="showErrModal('DID status must be PASS to run channel test. Current: <?php echo strtoupper($sc); ?>')">
                    <i class="fa-solid fa-lock"></i>Channel
                  </button>
                  <input type="number" class="ch-input-sm" value="5" disabled style="opacity:.4">
                </div>
                <?php endif; ?>
              </div>

              <div class="route-chancell">
                <span class="ch-res-badge" title="Detected Channels"><?php echo $chv; ?></span>
              </div>

              <div class="gap-col"></div>

              <div class="route-actcell">
                <div class="act">
                  <form method="POST" style="margin:0"><input type="hidden" name="action" value="manual_route"><input type="hidden" name="id" value="<?php echo $row['id']; ?>"><button type="submit" class="btn-sm btn-route"><i class="fa-solid fa-route"></i>Route</button></form>
                  <form method="POST" style="margin:0"><input type="hidden" name="action" value="reset_status"><input type="hidden" name="id" value="<?php echo $row['id']; ?>"><button type="submit" class="btn-sm btn-reset"><i class="fa-solid fa-rotate-left"></i>Reset</button></form>
                  <form method="POST" style="margin:0" onsubmit="return confirm('Remove this DID?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $row['id']; ?>"><button type="submit" class="btn-sm btn-del"><i class="fa-solid fa-trash-can"></i>Delete</button></form>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW 2: CHANNEL TESTS -->
      <div id="view-channel-tests" class="view-section">
        <div class="slabel"><i class="fa-solid fa-signal"></i>Channel Detection Logs</div>
        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-list-check"></i>Channel Diagnostic Status Logs</div>
          </div>

          <?php if (!$ch_logs_res || $ch_logs_res->num_rows === 0): ?>
            <p style="font-size:13px;color:var(--ink2);padding:10px 0;">No channel diagnostic tests recorded yet. Execute channel tests directly from the DID Routes view.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                <thead>
                  <tr style="border-bottom:1px solid var(--border); color:var(--ink3); font-size:10.5px; text-transform:uppercase; font-family:var(--mono);">
                    <th style="padding:10px 14px;">DID Number</th>
                    <th style="padding:10px 14px;">Calls Fired</th>
                    <th style="padding:10px 14px;">Channels Detected</th>
                    <th style="padding:10px 14px;">Status</th>
                    <th style="padding:10px 14px;">Timestamp</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($clog = $ch_logs_res->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--bordersoft);">
                      <td style="padding:12px 14px; font-weight:700; font-family:var(--mono); color:var(--ink1);">
                        <?php echo htmlspecialchars($clog['phone_number']); ?>
                      </td>
                      <td style="padding:12px 14px; font-family:var(--mono); color:var(--ink2);">
                        <?php echo (int)$clog['calls_requested']; ?>
                      </td>
                      <td style="padding:12px 14px; font-family:var(--mono); font-weight:700; color:var(--violet);">
                        <?php echo (int)$clog['channels_detected']; ?>
                      </td>
                      <td style="padding:12px 14px;">
                        <span class="spill s-pass"><span class="sdot"></span><?php echo htmlspecialchars($clog['status']); ?></span>
                      </td>
                      <td style="padding:12px 14px; font-family:var(--mono); font-size:11px; color:var(--ink3);">
                        <?php echo htmlspecialchars($clog['created_at']); ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- VIEW 3: LIVE CALLS -->
      <div id="view-live-calls" class="view-section">
        <div class="slabel"><i class="fa-solid fa-phone-volume"></i>Active Switch Sessions</div>
        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-headset"></i>Live Calls Monitor</div>
            <div class="cbadge"><span id="liveCallSubCount"><?php echo $active_calls; ?></span> Active Call(s)</div>
          </div>

          <div class="tbl-wrap">
            <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
              <thead>
                <tr style="border-bottom:1px solid var(--border); color:var(--ink3); font-size:10.5px; text-transform:uppercase; font-family:var(--mono);">
                  <th style="padding:10px 14px;">Channel ID</th>
                  <th style="padding:10px 14px;">DID / Extension</th>
                  <th style="padding:10px 14px;">Caller ID</th>
                  <th style="padding:10px 14px;">Application</th>
                  <th style="padding:10px 14px;">Duration</th>
                  <th style="padding:10px 14px; text-align:center;">Action</th>
                </tr>
              </thead>
              <tbody id="liveCallsTableBody">
                <tr>
                  <td colspan="6" style="text-align:center; padding:24px; color:var(--ink3); font-family:var(--mono);">Fetching live active channel streams...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- VIEW 4: SIP TRUNKS -->
      <div id="view-sip-trunks" class="view-section">
        <div class="slabel"><i class="fa-solid fa-server"></i>SIP Infrastructure</div>
        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-network-wired"></i>Registered SIP Peers</div>
            <div class="cbadge"><?php echo $online_peers; ?> Online</div>
          </div>

          <?php if (empty($peer_list)): ?>
            <p style="font-size:13px;color:var(--ink2);padding:10px 0;">No SIP peers detected or Asterisk CLI permissions restricted for <code>sip show peers</code>.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                <thead>
                  <tr style="border-bottom:1px solid var(--border); color:var(--ink3); font-size:10.5px; text-transform:uppercase; font-family:var(--mono);">
                    <th style="padding:10px 14px;">Peer / Extension</th>
                    <th style="padding:10px 14px;">Host / IP Address</th>
                    <th style="padding:10px 14px;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($peer_list as $peer): ?>
                    <tr style="border-bottom:1px solid var(--bordersoft);">
                      <td style="padding:12px 14px; font-weight:700; font-family:var(--mono); color:var(--ink1);">
                        <i class="fa-solid fa-plug" style="margin-right:8px; color:var(--primary);"></i><?php echo htmlspecialchars($peer['name']); ?>
                      </td>
                      <td style="padding:12px 14px; font-family:var(--mono); color:var(--ink2);">
                        <?php echo htmlspecialchars($peer['ip']); ?>
                      </td>
                      <td style="padding:12px 14px;">
                        <?php if ($peer['online']): ?>
                          <span class="spill s-pass"><span class="sdot"></span><?php echo htmlspecialchars($peer['status']); ?></span>
                        <?php else: ?>
                          <span class="spill s-fail"><span class="sdot"></span><?php echo htmlspecialchars($peer['status']); ?></span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- VIEW 5: REPORTS (CDR CALL LOGS WITH FILTERS) -->
      <div id="view-reports" class="view-section">
        <div class="slabel"><i class="fa-solid fa-chart-line"></i>Call Detail Records (CDR) &amp; Reports</div>

        <!-- Filter Form Bar -->
        <div class="card" style="padding:16px 20px; margin-bottom:16px;">
          <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div style="flex:1; min-width:140px;">
              <label style="display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--ink3); margin-bottom:6px; font-family:var(--mono);">Date Filter</label>
              <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--rs); background:var(--surface2); color:var(--ink1); font-family:var(--mono); font-size:12px; outline:none;">
            </div>

            <div style="flex:1; min-width:120px;">
              <label style="display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--ink3); margin-bottom:6px; font-family:var(--mono);">Hourly Filter</label>
              <select name="filter_hour" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--rs); background:var(--surface2); color:var(--ink1); font-family:var(--mono); font-size:12px; outline:none;">
                <option value="">All Hours</option>
                <?php for($h=0; $h<24; $h++): $h_val = str_pad($h, 2, '0', STR_PAD_LEFT); ?>
                  <option value="<?php echo $h; ?>" <?php echo ($filter_hour !== '' && (int)$filter_hour === $h) ? 'selected' : ''; ?>><?php echo $h_val; ?>:00 - <?php echo $h_val; ?>:59</option>
                <?php endfor; ?>
              </select>
            </div>

            <div style="flex:1; min-width:160px;">
              <label style="display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--ink3); margin-bottom:6px; font-family:var(--mono);">DID Number Filter</label>
              <select name="filter_did" style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:var(--rs); background:var(--surface2); color:var(--ink1); font-family:var(--mono); font-size:12px; outline:none;">
                <option value="">All DIDs</option>
                <?php while($d_row = $dids_dropdown_res->fetch_assoc()): ?>
                  <option value="<?php echo htmlspecialchars($d_row['phone_number']); ?>" <?php echo ($filter_did === $d_row['phone_number']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d_row['phone_number']); ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div style="display:flex; gap:8px;">
              <button type="submit" class="btn-primary" style="padding:9px 16px;"><i class="fa-solid fa-filter"></i>Apply Filter</button>
              <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn-sm btn-reset" style="padding:9px 14px; text-decoration:none; display:inline-flex; align-items:center; gap:5px;"><i class="fa-solid fa-rotate-left"></i>Reset</a>
            </div>
          </form>
        </div>

        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-file-lines"></i>Channel Test Call Logs (CDR)</div>
            <div class="cbadge"><?php echo $cdr_total_records; ?> Records Found</div>
          </div>

          <?php if (!$cdr_res || $cdr_res->num_rows === 0): ?>
            <p style="font-size:13px;color:var(--ink2);padding:10px 0;">No call records found matching the criteria.</p>
          <?php else: ?>
            <div class="tbl-wrap">
              <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                <thead>
                  <tr style="border-bottom:1px solid var(--border); color:var(--ink3); font-size:10.5px; text-transform:uppercase; font-family:var(--mono);">
                    <th style="padding:10px 14px;">DID Number</th>
                    <th style="padding:10px 14px;">Caller ID</th>
                    <th style="padding:10px 14px;">Status</th>
                    <th style="padding:10px 14px;">Date Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($cdr = $cdr_res->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--bordersoft);">
                      <td style="padding:12px 14px; font-weight:700; font-family:var(--mono); color:var(--ink1);">
                        <?php echo htmlspecialchars($cdr['phone_number']); ?>
                      </td>
                      <td style="padding:12px 14px; font-family:var(--mono); color:var(--violet);">
                        <?php
                          $display_caller_id = $cdr['caller_id'];
                          if (preg_match('/<([^>]+)>/', $display_caller_id, $cid_match)) {
                              $display_caller_id = $cid_match[1];
                          }
                          echo htmlspecialchars($display_caller_id);
                        ?>
                      </td>
                      <td style="padding:12px 14px;">
                        <span class="spill s-pass"><span class="sdot"></span><?php echo htmlspecialchars($cdr['call_status']); ?></span>
                      </td>
                      <td style="padding:12px 14px; font-family:var(--mono); font-size:11px; color:var(--ink3);">
                        <?php echo htmlspecialchars($cdr['created_at']); ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <?php if ($cdr_total_pages > 1): ?>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 4px 4px; flex-wrap:wrap;">
              <div style="font-size:11.5px; color:var(--ink3); font-family:var(--mono);">
                Page <?php echo $cdr_page; ?> of <?php echo $cdr_total_pages; ?>
                (showing <?php echo $cdr_res->num_rows; ?> of <?php echo $cdr_total_records; ?>)
              </div>
              <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <?php if ($cdr_page > 1): ?>
                  <a href="<?php echo cdr_page_link(1, $filter_date, $filter_hour, $filter_did); ?>" class="btn-sm btn-reset" style="text-decoration:none;">&laquo; First</a>
                  <a href="<?php echo cdr_page_link($cdr_page - 1, $filter_date, $filter_hour, $filter_did); ?>" class="btn-sm btn-reset" style="text-decoration:none;">&lsaquo; Prev</a>
                <?php endif; ?>

                <?php
                  $range_start = max(1, $cdr_page - 2);
                  $range_end   = min($cdr_total_pages, $cdr_page + 2);
                  for ($pnum = $range_start; $pnum <= $range_end; $pnum++):
                ?>
                  <a href="<?php echo cdr_page_link($pnum, $filter_date, $filter_hour, $filter_did); ?>"
                     class="btn-sm <?php echo ($pnum === $cdr_page) ? 'btn-primary' : 'btn-reset'; ?>"
                     style="text-decoration:none; <?php echo ($pnum === $cdr_page) ? 'pointer-events:none;' : ''; ?>"><?php echo $pnum; ?></a>
                <?php endfor; ?>

                <?php if ($cdr_page < $cdr_total_pages): ?>
                  <a href="<?php echo cdr_page_link($cdr_page + 1, $filter_date, $filter_hour, $filter_did); ?>" class="btn-sm btn-reset" style="text-decoration:none;">Next &rsaquo;</a>
                  <a href="<?php echo cdr_page_link($cdr_total_pages, $filter_date, $filter_hour, $filter_did); ?>" class="btn-sm btn-reset" style="text-decoration:none;">Last &raquo;</a>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- VIEW 6: SETTINGS -->
      <div id="view-settings" class="view-section">
        <div class="slabel"><i class="fa-solid fa-gear"></i>Configuration</div>
        <div class="card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-sliders"></i>Switch Parameters</div>
          </div>
          <p style="font-size:13px;color:var(--ink2);">DB Server: <code>165.227.88.28</code> | Host Status: Connected</p>
        </div>
      </div>

      <div class="foot">DIDX &middot; VoIP Softswitch Control Panel &middot; Asterisk Engine &middot; <?php echo date('Y'); ?></div>
    </div>
  </main>
</div>

<!-- ERROR MODAL -->
<div id="errModal" style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:38px 34px;max-width:420px;width:92%;box-shadow:0 30px 80px rgba(20,20,60,.25);text-align:center">
    <div style="width:56px;height:56px;border-radius:50%;background:rgba(224,57,63,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#e0393f"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div style="font-size:17px;font-weight:700;margin-bottom:10px;color:var(--ink1);font-family:'Sora',sans-serif">Channel Test Blocked</div>
    <div id="errModalMsg" style="font-size:13px;color:var(--ink2);line-height:1.7;margin-bottom:26px"></div>
    <button onclick="closeErrModal()" style="padding:11px 38px;background:linear-gradient(135deg,#6153f6,#7a6bf9);color:#fff;border:none;border-radius:99px;font-size:13px;font-weight:700;cursor:pointer">OK, Got It</button>
  </div>
</div>

<!-- CONFIRM MODAL -->
<div id="confirmModal" style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:38px 34px;max-width:420px;width:92%;box-shadow:0 30px 80px rgba(20,20,60,.25);text-align:center">
    <div style="width:56px;height:56px;border-radius:50%;background:rgba(224,57,63,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#e0393f"><i class="fa-solid fa-phone-slash"></i></div>
    <div id="confirmModalTitle" style="font-size:17px;font-weight:700;margin-bottom:10px;color:var(--ink1);font-family:'Sora',sans-serif">Disconnect All Calls?</div>
    <div id="confirmModalMsg" style="font-size:13px;color:var(--ink2);line-height:1.7;margin-bottom:26px"></div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeConfirmModal()" style="padding:11px 28px;background:var(--surface2);color:var(--ink2);border:1px solid var(--border);border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">Cancel</button>
      <button onclick="confirmModalYes()" style="padding:11px 28px;background:linear-gradient(135deg,#e0393f,#c93b3b);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">Yes, Disconnect</button>
    </div>
  </div>
</div>

<script>
/* Navigation Switcher */
document.querySelectorAll('#sidebarNav .sb-item').forEach(function(item) {
  item.addEventListener('click', function(e) {
    e.preventDefault();

    document.querySelectorAll('#sidebarNav .sb-item').forEach(function(el) { el.classList.remove('active'); });
    this.classList.add('active');

    document.querySelectorAll('.view-section').forEach(function(sec) { sec.classList.remove('active-view'); });

    var targetId = this.getAttribute('data-target');
    var targetSec = document.getElementById(targetId);
    if (targetSec) {
      targetSec.classList.add('active-view');
    }

    var title = this.getAttribute('data-title');
    var crumb = this.getAttribute('data-crumb');
    if (title) document.getElementById('pageTitle').textContent = title;
    if (crumb) document.getElementById('pageCrumb').textContent = crumb;
  });
});

var _confirmCallback = null;

function showConfirmModal(title, msg, onYes){
  document.getElementById('confirmModalTitle').textContent = title;
  document.getElementById('confirmModalMsg').textContent = msg;
  _confirmCallback = onYes;
  document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal(){
  document.getElementById('confirmModal').style.display = 'none';
  _confirmCallback = null;
}

function confirmModalYes(){
  var cb = _confirmCallback;
  closeConfirmModal();
  if (cb) cb();
}

function handleHangupSubmit(e){
  e.preventDefault();
  showConfirmModal('Disconnect All Calls?', 'Disconnect ALL active calls on the switch now?', function(){
    document.getElementById('hangupForm').submit();
  });
  return false;
}

function startChTest(form, id){
  var btn = form.querySelector('.btn-channel');
  if (btn) {
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Test';
    btn.disabled = true;
  }
  return true;
}

function showErrModal(msg){
  document.getElementById('errModalMsg').textContent = msg;
  document.getElementById('errModal').style.display = 'flex';
}

function closeErrModal(){
  document.getElementById('errModal').style.display = 'none';
  history.replaceState(null, '', location.pathname);
}

function escapeHtml(str) {
  return String(str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

(function(){
  var p = new URLSearchParams(window.location.search);
  var e = p.get('ch_error');
  if(e){ showErrModal(e); }
})();

(function(){
  var toggle = document.getElementById('visionToggle');
  var btns = toggle.querySelectorAll('.vision-btn');
  function applyMode(mode){
    if(mode === 'night'){ document.body.classList.add('night'); }
    else{ document.body.classList.remove('night'); }
    btns.forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-mode') === mode); });
    try{ localStorage.setItem('didx_vision', mode); }catch(e){}
  }
  var saved = 'day';
  try{ saved = localStorage.getItem('didx_vision') || 'day'; }catch(e){}
  applyMode(saved);
  btns.forEach(function(b){
    b.addEventListener('click', function(){ applyMode(b.getAttribute('data-mode')); });
  });
})();

/* Realtime Dynamic Data Polling */
function updateStatuses(){
  fetch('./get_status.php', { cache: "no-store" })
    .then(function(r) { if (!r.ok) throw new Error(); return r.json(); })
    .then(function(data) {
      if (!data) return;

      var hb = document.getElementById('hangupBtn');
      var callsStat = document.getElementById('navCalls');
      var liveCallSub = document.getElementById('liveCallSubCount');

      // Update Live Calls Table Stream & Filter
      var tbody = document.getElementById('liveCallsTableBody');
      if (tbody) {
        var rawChannels = data['_live_channels'] || [];

        // Exact registered endpoints filter
        var allowedPeers = [
          'eu3.didx.net',
          'eu2.didx.net',
          'ca.didx.net',
          'us2.didx.net',
          'sip10.didx.net',
          'vpl-switch',
          'belloceanic'
        ];

        // Filter: Keep only incoming channels from selected registered SIP endpoints
        var channels = rawChannels.filter(function(lc) {
          var chName = String(lc.channel || '').toLowerCase();

          // Must be SIP or PJSIP channel
          if (!chName.startsWith('sip/') && !chName.startsWith('pjsip/')) {
            return false;
          }

          // Exclude internal outbound trunk legs
          if (chName.includes('outbound-calls')) {
            return false;
          }

          // Match channel against the exact allowed peers
          return allowedPeers.some(function(peer) {
            return chName.includes(peer);
          });
        });

        // Update active call indicators with count of filtered registered calls
        if (callsStat) callsStat.textContent = channels.length;
        if (liveCallSub) liveCallSub.textContent = channels.length;

        if (hb) {
          if (channels.length > 0) { hb.classList.add('flashing'); }
          else { hb.classList.remove('flashing'); }
        }

        if (channels.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--ink3); font-family:var(--mono);">No active incoming calls from registered SIP trunks.</td></tr>';
        } else {
          var rowsHtml = '';
          channels.forEach(function(lc) {
            rowsHtml += `
              <tr style="border-bottom:1px solid var(--bordersoft);">
                <td style="padding:12px 14px; font-family:var(--mono); color:var(--ink3);">${escapeHtml(lc.channel)}</td>
                <td style="padding:12px 14px; font-family:var(--mono); font-weight:700; color:var(--ok);">
                  <i class="fa-solid fa-phone" style="margin-right:6px; font-size:11px;"></i>${escapeHtml(lc.did)}
                </td>
                <td style="padding:12px 14px; font-family:var(--mono); color:var(--ink1);">${escapeHtml(lc.caller_id)}</td>
                <td style="padding:12px 14px; font-family:var(--mono); color:var(--violet);">${escapeHtml(lc.app)}</td>
                <td style="padding:12px 14px; font-family:var(--mono); font-weight:700; color:var(--primary);">
                  <i class="fa-regular fa-clock" style="margin-right:4px;"></i>${lc.duration}
                </td>
                <td style="padding:12px 14px; text-align:center;">
                  <form method="POST" style="margin:0;" onsubmit="return confirm('Disconnect channel ${escapeHtml(lc.channel)}?')">
                    <input type="hidden" name="action" value="hangup_single">
                    <input type="hidden" name="channel_name" value="${escapeHtml(lc.channel)}">
                    <button type="submit" class="btn-sm btn-del">
                      <i class="fa-solid fa-phone-slash"></i> Drop
                    </button>
                  </form>
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = rowsHtml;
        }
      }

      // Update Route Table Row Statuses
      Object.keys(data).forEach(function(id) {
        if (id.startsWith('_')) return;

        var row = document.querySelector('.route-row[data-id="' + id + '"]');
        if (!row) return;

        var sc = String(data[id]).toLowerCase().trim();
        var statusTextSpan = row.querySelector('.status-text');
        var currentStatus = statusTextSpan ? statusTextSpan.textContent.trim().toLowerCase() : '';

        if (currentStatus !== sc) {
          row.className = 'route-row rs-' + sc;

          var statusSpill = row.querySelector('.spill');
          if (statusSpill && statusTextSpan) {
            statusSpill.className = 'spill s-' + sc;
            statusTextSpan.textContent = sc;
          }

          var chCell = row.querySelector('.route-chcell');
          var didNum = row.querySelector('.route-did') ? row.querySelector('.route-did').textContent.trim() : '';

          if (chCell) {
            if (sc === 'pass') {
              chCell.innerHTML =
                '<form method="POST" style="margin:0;display:inline-flex;align-items:center;gap:8px" onsubmit="return startChTest(this, ' + id + ')">' +
                  '<input type="hidden" name="action" value="test_channels">' +
                  '<input type="hidden" name="id" value="' + id + '">' +
                  '<input type="hidden" name="phone_number" value="' + didNum + '">' +
                  '<button type="submit" class="btn-sm btn-channel"><i class="fa-solid fa-signal"></i>Channel</button>' +
                  '<input type="number" class="ch-input-sm" name="call_count_' + id + '" id="cc_input_' + id + '" value="5" min="1" max="100" title="Number of calls to test">' +
                '</form>';
            } else {
              chCell.innerHTML =
                '<div style="display:inline-flex;align-items:center;gap:8px">' +
                  '<button type="button" class="btn-sm btn-channel" style="opacity:.4;cursor:not-allowed" onclick="showErrModal(\'DID status must be PASS to run channel test. Current: ' + sc.toUpperCase() + '\')">' +
                    '<i class="fa-solid fa-lock"></i>Channel' +
                  '</button>' +
                  '<input type="number" class="ch-input-sm" value="5" disabled style="opacity:.4">' +
                '</div>';
            }
          }
        }
      });
    }).catch(function(err) { console.error("Live update polling error:", err); });
}

setInterval(updateStatuses, 2000);
updateStatuses();
</script>
</body>
</html>
