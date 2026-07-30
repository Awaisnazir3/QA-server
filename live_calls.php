<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
$db_host = "165.227.88.28"; $db_user = "admin"; $db_pass = "12343211"; $db_name = "telecom_db";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'hangup_channel' && !empty($_POST['channel'])) {
        $chan = escapeshellarg($_POST['channel']);
        shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup {$chan}' 2>/dev/null");
    } elseif ($action === 'hangup_all') {
        shell_exec("sudo /usr/sbin/asterisk -rx 'channel request hangup all' 2>/dev/null");
    }
    header("Location: live_calls.php"); exit;
}

// Fetch channels from Asterisk CLI
$channels_raw = shell_exec("sudo /usr/sbin/asterisk -rx 'core show channels verbose' 2>/dev/null") ?: '';
$parsed_calls = [];
if ($channels_raw) {
    $lines = explode("\n", trim($channels_raw));
    foreach ($lines as $line) {
        if (preg_match('/^(SIP\/[^\s]+|Local\/[^\s]+)\s+([^\s]+)\s+([^\s]+)\s+([0-9]+)\s+([^\s]+)/i', trim($line), $m)) {
            $parsed_calls[] = [
                'channel'     => $m[1],
                'context'     => $m[2],
                'exten'       => $m[3],
                'priority'    => $m[4],
                'state'       => $m[5]
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>DIDX — Live Connected Calls</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#f3f4f9;--surface:#ffffff;--border:#e6e8f2;--ink1:#171a2c;--ink3:#9499b3;--primary:#6153f6;--danger:#e0393f;--mono:'JetBrains Mono',monospace;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink1);margin:0;padding:25px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;max-width:1200px;margin:auto}
.tbl{width:100%;border-collapse:collapse;margin-top:15px;font-family:var(--mono);font-size:13px}
.tbl th,.tbl td{padding:12px;border-bottom:1px solid var(--border);text-align:left}
.tbl th{background:#f8f9fd;color:var(--ink3);font-size:11px;text-transform:uppercase}
.btn-danger{background:rgba(224,57,63,.1);color:var(--danger);border:1px solid rgba(224,57,63,.2);padding:6px 12px;border-radius:6px;cursor:pointer;font-weight:600}
</style>
</head>
<body>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2><i class="fa-solid fa-phone-volume" style="color:var(--primary)"></i> Live Active Calls (<?php echo count($parsed_calls); ?>)</h2>
    <form method="POST"><input type="hidden" name="action" value="hangup_all">
      <button type="submit" class="btn-danger"><i class="fa-solid fa-phone-slash"></i> Hangup All Calls</button>
    </form>
  </div>
  <table class="tbl">
    <thead>
      <tr><th>Channel Name</th><th>Context</th><th>Extension</th><th>State</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php if(empty($parsed_calls)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--ink3)">No active call channels currently online.</td></tr>
      <?php else: foreach($parsed_calls as $c): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['channel']); ?></td>
          <td><?php echo htmlspecialchars($c['context']); ?></td>
          <td><?php echo htmlspecialchars($c['exten']); ?></td>
          <td><span style="color:#0fa66a;font-weight:700"><?php echo htmlspecialchars($c['state']); ?></span></td>
          <td>
            <form method="POST" style="margin:0"><input type="hidden" name="action" value="hangup_channel">
              <input type="hidden" name="channel" value="<?php echo htmlspecialchars($c['channel']); ?>">
              <button type="submit" class="btn-danger"><i class="fa-solid fa-xmark"></i> Hangup</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>