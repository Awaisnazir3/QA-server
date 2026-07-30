<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
$db_host = "165.227.88.28"; $db_user = "admin"; $db_pass = "12343211"; $db_name = "telecom_db";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

$history = $conn->query("SELECT * FROM channel_test_logs ORDER BY created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>DIDX — Channel Test History</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#f3f4f9;--surface:#ffffff;--border:#e6e8f2;--ink1:#171a2c;--primary:#6153f6;--mono:'JetBrains Mono',monospace;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink1);margin:0;padding:25px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;max-width:1200px;margin:auto}
.tbl{width:100%;border-collapse:collapse;margin-top:15px;font-family:var(--mono);font-size:13px}
.tbl th,.tbl td{padding:12px;border-bottom:1px solid var(--border);text-align:left}
.tbl th{background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase}
.badge{padding:4px 8px;border-radius:6px;background:rgba(97,83,246,.1);color:var(--primary);font-weight:700}
</style>
</head>
<body>
<div class="card">
  <h2><i class="fa-solid fa-signal" style="color:var(--primary)"></i> Channel Test Audit History</h2>
  <table class="tbl">
    <thead>
      <tr><th>Test ID</th><th>Phone DID</th><th>Requested Calls</th><th>Channels Detected</th><th>Execution Time</th></tr>
    </thead>
    <tbody>
      <?php if($history && $history->num_rows > 0): while($r = $history->fetch_assoc()): ?>
        <tr>
          <td>#<?php echo $r['id']; ?></td>
          <td><strong><?php echo htmlspecialchars($r['phone_number']); ?></strong></td>
          <td><?php echo $r['calls_requested']; ?> Calls</td>
          <td><span class="badge"><?php echo $r['channels_detected']; ?> Channels</span></td>
          <td><?php echo $r['created_at']; ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="5" style="text-align:center">No channel test history recorded yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>