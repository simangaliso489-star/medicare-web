<?php
// ============================================================
//  MediCare+ — Admin: Manage Appointments
// ============================================================
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../php/config.php';

$pdo = getDB();
$msg = '';

// Handle status change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $status = $_GET['status'];
    $allowed = ['pending','confirmed','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare('UPDATE appointments SET status=? WHERE id=?')->execute([$status, $id]);
    }
    header('Location: appointments.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM appointments WHERE id=?')->execute([$id]);
    header('Location: appointments.php?deleted=1');
    exit;
}
if (isset($_GET['deleted'])) $msg = 'Appointment removed.';

$filter = $_GET['filter'] ?? 'all';
$sql    = 'SELECT a.*, u.fullname AS patient_name, d.fullname AS doctor_name, d.specialty
           FROM appointments a
           JOIN users u ON a.user_id = u.id
           JOIN doctors d ON a.doctor_id = d.id';
$params = [];
if ($filter !== 'all') {
    $sql    .= ' WHERE a.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY a.appt_date DESC, a.appt_time DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointments | MediCare+ Admin</title>
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
    .admin-topbar { background: var(--bg-card); border-bottom: 1px solid var(--border); padding: 16px 32px; }
    .admin-content { padding: 28px 32px; }
    .admin-table-card { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
    .admin-table-card .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing:.05em; background: var(--bg-main); }
    td { padding: 12px 20px; border-top: 1px solid var(--border); font-size: 0.85rem; vertical-align: middle; }
    .status-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
    .status-pill.pending   { background: hsl(40,100%,92%); color: hsl(40,90%,35%); }
    .status-pill.confirmed { background: hsl(210,100%,92%); color: hsl(210,90%,35%); }
    .status-pill.completed { background: hsl(142,70%,90%); color: hsl(142,70%,30%); }
    .status-pill.cancelled { background: hsl(0,70%,92%); color: hsl(0,70%,40%); }
    .status-select { border: 1px solid var(--border); border-radius: 8px; padding: 4px 8px; font-size: 0.8rem; background: var(--bg-card); color: var(--text-primary); cursor: pointer; }
    .action-link { color: var(--error); text-decoration: none; font-weight: 600; font-size: 0.8rem; }
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
      <a href="users.php"><i class="ri-group-line"></i> Patients</a>
      <a href="doctors.php"><i class="ri-nurse-line"></i> Doctors</a>
      <a href="appointments.php" class="active"><i class="ri-calendar-check-line"></i> Appointments</a>
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
      <h1 style="font-size:1.25rem; font-weight:700;"><i class="ri-calendar-check-line" style="color:var(--primary);"></i> Appointments</h1>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?><div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> <?= $msg ?></div><?php endif; ?>

      <!-- Filter Tabs -->
      <div class="filter-tabs" style="margin-bottom:20px;">
        <?php foreach (['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
          <a href="appointments.php?filter=<?= $val ?>"
             class="filter-tab <?= $filter === $val ? 'active' : '' ?>">
            <?= $lbl ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="admin-table-card">
        <div class="card-header">
          <h3 style="font-size:1rem; font-weight:700;">All Appointments (<?= count($appointments) ?>)</h3>
        </div>
        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date & Time</th>
                <th>Type</th>
                <th>Fee</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($appointments)): ?>
                <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:40px;">No appointments found.</td></tr>
              <?php else: ?>
                <?php foreach ($appointments as $a): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                    <td>
                      <?= htmlspecialchars($a['doctor_name']) ?>
                      <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($a['specialty']) ?></div>
                    </td>
                    <td>
                      <?= date('M j, Y', strtotime($a['appt_date'])) ?>
                      <div style="font-size:0.75rem; color:var(--text-muted);"><?= date('g:i A', strtotime($a['appt_time'])) ?></div>
                    </td>
                    <td><?= ucfirst($a['consult_type']) ?></td>
                    <td>$<?= number_format($a['fee'], 2) ?></td>
                    <td>
                      <!-- Quick status change -->
                      <select class="status-select" onchange="location='appointments.php?id=<?= $a['id'] ?>&status='+this.value">
                        <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                          <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td>
                      <a href="appointments.php?delete=<?= $a['id'] ?>" class="action-link"
                         onclick="return confirm('Delete this appointment?')">
                        <i class="ri-delete-bin-line"></i>
                      </a>
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
</div>

</body>
</html>
