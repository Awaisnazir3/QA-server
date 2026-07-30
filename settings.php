<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
$db_host = "165.227.88.28"; $db_user = "admin"; $db_pass = "12343211"; $db_name = "telecom_db";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $u = trim($_POST['username']);
        $p = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role = $_POST['role'] ?? 'admin';
        
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $u, $p, $role);
        if ($stmt->execute()) { $msg = "User added successfully!"; } else { $msg = "Error: Username exists."; }
        $stmt->close();
    } elseif ($action === 'delete_user') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM admin_users WHERE id={$id}");
        $msg = "User removed successfully.";
    }
}

$users = $conn->query("SELECT id, username, role, created_at FROM admin_users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>DIDX — Console Settings</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#f3f4f9;--surface:#ffffff;--border:#e6e8f2;--ink1:#171a2c;--primary:#6153f6;--mono:'JetBrains Mono',monospace;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink1);margin:0;padding:25px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;max-width:900px;margin:20px auto}
.tbl{width:100%;border-collapse:collapse;margin-top:15px;font-family:var(--mono);font-size:13px}
.tbl th,.tbl td{padding:12px;border-bottom:1px solid var(--border);text-align:left}
.tbl th{background:#f8f9fd;color:#9499b3;font-size:11px;text-transform:uppercase}
.btn-primary{background:var(--primary);color:#fff;padding:8px 16px;border:none;border-radius:6px;cursor:pointer;font-weight:600}
.inp{padding:8px;border:1px solid var(--border);border-radius:6px;font-family:var(--mono)}
</style>
</head>
<body>
<div class="card">
  <h2><i class="fa-solid fa-gear" style="color:var(--primary)"></i> Console System Settings & Admin Management</h2>
  <?php if($msg): ?><p style="color:#0fa66a;font-weight:700"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
  
  <h3>Create Console User</h3>
  <form method="POST" style="display:flex;gap:10px;align-items:center">
    <input type="hidden" name="action" value="add_user">
    <input type="text" name="username" class="inp" placeholder="Username" required>
    <input type="password" name="password" class="inp" placeholder="Password" required>
    <select name="role" class="inp"><option value="admin">Admin</option><option value="operator">Operator</option></select>
    <button type="submit" class="btn-primary">Add User</button>
  </form>

  <h3 style="margin-top:30px">Existing Users</h3>
  <table class="tbl">
    <thead>
      <tr><th>ID</th><th>Username</th><th>Role</th><th>Created At</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php if($users): while($u = $users->fetch_assoc()): ?>
        <tr>
          <td>#<?php echo $u['id']; ?></td>
          <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
          <td><?php echo strtoupper($u['role']); ?></td>
          <td><?php echo $u['created_at']; ?></td>
          <td>
            <form method="POST" style="margin:0" onsubmit="return confirm('Delete user?')">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
              <button type="submit" style="color:#e0393f;background:none;border:none;cursor:pointer;font-weight:700">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>