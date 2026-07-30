<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
$db_host = "165.227.88.28"; $db_user = "admin"; $db_pass = "12343211"; $db_name = "telecom_db";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

$filter_num = $_GET['search'] ?? '';
$sql = "SELECT * FROM cdr ";
if (!empty($filter_num)) {
    $clean = $conn->real_escape_string($filter_num);
    $sql .= "WHERE caller_id LIKE '%{$clean}%' OR destination LIKE '%{$clean}%' ";
}
$sql .= "ORDER BY start_time DESC LIMIT 100";
$cdrs = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>DIDX — Call Reports & CDRs</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#f3f4f9;--surface:#ffffff;--border:#e6e8f2;--ink1:#171a2c;--primary:#6153f6;--mono:'JetBrains Mono',monospace;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink1);margin:0;padding:25px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;max-width:1200px;margin:auto}
.tbl{width:100%;border-collapse:collapse;margin-top:15px;font-family:var(--mono);font-size:13px}
.tbl th,.tbl td{padding:12px;border-bottom:1px solid var(--border);text-align:left}
.tbl th{background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase}
.input-search{padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-family:var(--mono);width:250px}
</style>
</head>
<body>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Call Detail Records (CDR)</h2>
    <form method="GET">
      <input type="text" name="search" class="input-search" placeholder="Search Caller ID / DID..." value="<?php echo htmlspecialchars($filter_num); ?>">
    </form>
  </div>
  <table class="tbl">
    <thead>
      <tr><th>Date & Time</th><th>Caller ID</th><th>Destination</th><th>Duration</th><th>Billsec</th><th>Disposition</th></tr>
    </thead>
    <tbody>
      <?php if($cdrs && $cdrs->num_rows > 0): while($r = $cdrs->fetch_assoc()): ?>
        <tr>
          <td><?php echo $r['start_time']; ?></td>
          <td><?php echo htmlspecialchars($r['caller_id']); ?></td>
          <td><?php echo htmlspecialchars($r['destination']); ?></td>
          <td><?php echo $r['duration']; ?>s</td>
          <td><?php echo $r['billsec']; ?>s</td>
          <td><strong style="color:<?php echo $r['disposition']==='ANSWERED'?'#0fa66a':'#e0393f'; ?>"><?php echo $r['disposition']; ?></strong></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" style="text-align:center;color:#9499b3">No CDR logs found in telecom database.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>