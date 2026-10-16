<?php
// ============================================================
//  MediCare+ — Admin: Manage Patients
// ============================================================
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../php/config.php';

$pdo = getDB();
$msg = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='patient'")->execute([$id]);
    header('Location: users.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) $msg = 'Patient removed successfully.';

$search = trim($_GET['search'] ?? '');
$sql    = "SELECT u.*, 
           (SELECT COUNT(*) FROM appointments WHERE user_id = u.id) AS appt_count
           FROM users u WHERE u.role = 'patient'";
$params = [];
if ($search) {
    $sql    .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params  = ["%$search%","%$search%","%$search%"];
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Patients | MediCare+ Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="../style.css" />
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; min-height: 100vh; background: hsl(220,80%,8%); display: flex; flex-direction: column; flex-shrink: 0; }
    .admin-sidebar-logo { padding: 28px 24px; font-size: 1.2rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,.07); }
    .admin-sidebar-logo i { color: var(--primary); font-size: 1.5rem; }
    .admin-sidebar-badge { font-size: 0.6rem; background: rgba(99,102,241,.2); color: #818cf8; padding: 2px 8px; border-radius: 100px; font-weight: 700; text-transform: uppercase; letter-spacing:.06em; }
    .admin-nav { flex: 1; padding: 16px 0; }
    .admin-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: rgba(255,255,255,.55); font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all .2s; }
    .admin-nav a:hover, .admin-nav a.active { color: #fff; background: rgba(255,255,255,.07); }
    .admin-nav a.active { border-left: 3px solid var(--primary); }
    .admin-nav a i { font-size: 1.1rem; }
    .admin-main { flex: 1; background: var(--bg-main); overflow-y: auto; }
    .admin-topbar { background: var(--bg-card); border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; }
    .admin-content { padding: 28px 32px; }
    .admin-table-card { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
    .admin-table-card .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing:.05em; background: var(--bg-main); }
    td { padding: 14px 20px; border-top: 1px solid var(--border); font-size: 0.875rem; }
    .action-link { color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.8rem; margin-right: 10px; }
    .action-link.danger { color: var(--error); }
  </style>
</head>
<body>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-sidebar-logo">
      <i class="ri-hospital-line"></i>
      <div>MediCare+ <br><span class="admin-sidebar-badge">Admin</span></div>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php"><i class="ri-dashboard-line"></i> Dashboard</a>
      <a href="users.php" class="active"><i class="ri-group-line"></i> Patients</a>
      <a href="doctors.php"><i class="ri-nurse-line"></i> Doctors</a>
      <a href="appointments.php"><i class="ri-calendar-check-line"></i> Appointments</a>
      <a href="../dashboard.php"><i class="ri-home-5-line"></i> Patient View</a>
    </nav>
    <div style="padding: 24px;">
      <a href="../logout.php" class="btn btn-ghost" style="color:rgba(255,255,255,.5); width:100%; justify-content:flex-start; gap:12px; padding:12px 0;">
        <i class="ri-logout-box-r-line"></i> Log Out
      </a>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1 style="font-size:1.25rem; font-weight:700;"><i class="ri-group-line" style="color:var(--primary);"></i> Manage Patients</h1>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?><div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> <?= $msg ?></div><?php endif; ?>

      <!-- Search -->
      <form method="GET" action="users.php" style="margin-bottom:20px;">
        <div class="form-control" style="display:flex; align-items:center; gap:8px; max-width:400px;">
          <i class="ri-search-line" style="color:var(--text-muted);"></i>
          <input type="text" name="search" placeholder="Search by name, email or phone..."
                 value="<?= htmlspecialchars($search) ?>"
                 style="flex:1; border:none; outline:none; background:transparent; font-size:0.9rem; color:var(--text-primary);" />
          <?php if ($search): ?><a href="users.php" style="color:var(--text-muted);"><i class="ri-close-line"></i></a><?php endif; ?>
        </div>
      </form>

      <div class="admin-table-card">
        <div class="card-header">
          <h3 style="font-size:1rem; font-weight:700;">All Patients (<?= count($users) ?>)</h3>
        </div>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Gender</th>
              <th>Appointments</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px;">No patients found.</td></tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($u['fullname']) ?></strong></td>
                  <td style="font-size:0.82rem;"><?= htmlspecialchars($u['email']) ?></td>
                  <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                  <td><?= $u['gender'] ? ucfirst($u['gender']) : '—' ?></td>
                  <td style="text-align:center;"><strong><?= $u['appt_count'] ?></strong></td>
                  <td style="font-size:0.8rem; color:var(--text-muted);"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <a href="users.php?delete=<?= $u['id'] ?>" class="action-link danger"
                       onclick="return confirm('Delete patient <?= addslashes($u['fullname']) ?>?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
