<?php
// ============================================================
//  MediCare+ — Admin Dashboard
// ============================================================
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../php/config.php';

$pdo = getDB();

// Stats
$totalPatients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn();
$totalDoctors  = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$totalAppts    = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppts  = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn();
$todayAppts    = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appt_date = CURDATE()")->fetchColumn();

// Recent appointments
$recentAppts = $pdo->query(
    'SELECT a.*, u.fullname AS patient_name, d.fullname AS doctor_name, d.specialty
     FROM appointments a
     JOIN users u ON a.user_id = u.id
     JOIN doctors d ON a.doctor_id = d.id
     ORDER BY a.created_at DESC LIMIT 8'
)->fetchAll();

// Recent registrations
$recentUsers = $pdo->query(
    "SELECT * FROM users WHERE role='patient' ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="../style.css" />
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar {
      width: 260px; min-height: 100vh; background: hsl(220,80%,8%);
      padding: 0; display: flex; flex-direction: column; flex-shrink: 0;
    }
    .admin-sidebar-logo {
      padding: 28px 24px; font-size: 1.2rem; font-weight: 800;
      color: #fff; display: flex; align-items: center; gap: 10px;
      border-bottom: 1px solid rgba(255,255,255,.07);
    }
    .admin-sidebar-logo i { color: var(--primary); font-size: 1.5rem; }
    .admin-sidebar-badge {
      font-size: 0.6rem; background: rgba(99,102,241,.2); color: #818cf8;
      padding: 2px 8px; border-radius: 100px; font-weight: 700;
      text-transform: uppercase; letter-spacing:.06em;
    }
    .admin-nav { flex: 1; padding: 16px 0; }
    .admin-nav a {
      display: flex; align-items: center; gap: 12px; padding: 12px 24px;
      color: rgba(255,255,255,.55); font-size: 0.9rem; font-weight: 500;
      text-decoration: none; transition: all .2s;
    }
    .admin-nav a:hover, .admin-nav a.active {
      color: #fff; background: rgba(255,255,255,.07);
    }
    .admin-nav a.active { border-left: 3px solid var(--primary); }
    .admin-nav a i { font-size: 1.1rem; }
    .admin-main { flex: 1; background: var(--bg-main); padding: 0; overflow-y: auto; }
    .admin-topbar {
      background: var(--bg-card); border-bottom: 1px solid var(--border);
      padding: 16px 32px; display: flex; align-items: center; justify-content: space-between;
    }
    .admin-topbar h1 { font-size: 1.25rem; font-weight: 700; }
    .admin-content { padding: 28px 32px; }
    .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 28px; }
    .stat-card {
      background: var(--bg-card); border-radius: 16px; padding: 24px;
      border: 1px solid var(--border); display: flex; flex-direction: column; gap: 8px;
    }
    .stat-card .sc-icon {
      width: 44px; height: 44px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
    }
    .stat-card .sc-value { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-card .sc-label { color: var(--text-muted); font-size: 0.85rem; }
    .admin-table-card { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 24px; }
    .admin-table-card .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .admin-table-card .card-header h3 { font-size: 1rem; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing:.05em; background: var(--bg-main); }
    td { padding: 14px 20px; border-top: 1px solid var(--border); font-size: 0.875rem; }
    .status-pill {
      display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px;
      border-radius: 100px; font-size: 0.75rem; font-weight: 600;
    }
    .status-pill.pending   { background: hsl(40,100%,92%); color: hsl(40,90%,35%); }
    .status-pill.confirmed { background: hsl(210,100%,92%); color: hsl(210,90%,35%); }
    .status-pill.completed { background: hsl(142,70%,90%); color: hsl(142,70%,30%); }
    .status-pill.cancelled { background: hsl(0,70%,92%); color: hsl(0,70%,40%); }
    .admin-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; }
    @media (max-width: 900px) { .admin-grid { grid-template-columns: 1fr; } .admin-sidebar { display: none; } }
  </style>
</head>
<body>

<div class="admin-layout">

  <!-- Admin Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-sidebar-logo">
      <i class="ri-hospital-line"></i>
      <div>
        MediCare+ <br>
        <span class="admin-sidebar-badge">Admin</span>
      </div>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="active"><i class="ri-dashboard-line"></i> Dashboard</a>
      <a href="users.php"><i class="ri-group-line"></i> Patients</a>
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

  <!-- Admin Main -->
  <div class="admin-main">
    <div class="admin-topbar">
      <h1><i class="ri-dashboard-line" style="color:var(--primary);"></i> Dashboard</h1>
      <div style="display:flex; align-items:center; gap:12px;">
        <span style="color:var(--text-muted); font-size:0.85rem;"><?= date('D, M j, Y') ?></span>
        <div style="display:flex; align-items:center; gap:8px; font-weight:600;">
          <i class="ri-shield-user-line" style="color:var(--primary);"></i>
          <?= htmlspecialchars(adminUser()['fullname']) ?>
        </div>
      </div>
    </div>

    <div class="admin-content">
      <!-- Stat Cards -->
      <div class="stat-cards">
        <div class="stat-card">
          <div class="sc-icon" style="background:hsl(210,100%,92%); color:hsl(210,90%,45%);">
            <i class="ri-group-line"></i>
          </div>
          <div class="sc-value"><?= $totalPatients ?></div>
          <div class="sc-label">Total Patients</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:hsl(142,70%,90%); color:hsl(142,70%,35%);">
            <i class="ri-nurse-line"></i>
          </div>
          <div class="sc-value"><?= $totalDoctors ?></div>
          <div class="sc-label">Doctors</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:hsl(40,100%,92%); color:hsl(40,90%,35%);">
            <i class="ri-calendar-check-line"></i>
          </div>
          <div class="sc-value"><?= $totalAppts ?></div>
          <div class="sc-label">Total Appointments</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:hsl(280,80%,92%); color:hsl(280,70%,45%);">
            <i class="ri-time-line"></i>
          </div>
          <div class="sc-value"><?= $pendingAppts ?></div>
          <div class="sc-label">Pending</div>
        </div>
        <div class="stat-card">
          <div class="sc-icon" style="background:hsl(0,80%,92%); color:hsl(0,70%,45%);">
            <i class="ri-calendar-todo-line"></i>
          </div>
          <div class="sc-value"><?= $todayAppts ?></div>
          <div class="sc-label">Today's Appointments</div>
        </div>
      </div>

      <div class="admin-grid">
        <!-- Recent Appointments -->
        <div class="admin-table-card">
          <div class="card-header">
            <h3>Recent Appointments</h3>
            <a href="appointments.php" style="color:var(--primary); font-size:0.85rem; font-weight:600;">View All</a>
          </div>
          <table>
            <thead>
              <tr>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date & Time</th>
                <th>Type</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentAppts as $a): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                  <td>
                    <?= htmlspecialchars($a['doctor_name']) ?>
                    <div style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($a['specialty']) ?></div>
                  </td>
                  <td>
                    <?= date('M j, Y', strtotime($a['appt_date'])) ?>
                    <div style="font-size:0.78rem; color:var(--text-muted);"><?= date('g:i A', strtotime($a['appt_time'])) ?></div>
                  </td>
                  <td><?= ucfirst($a['consult_type']) ?></td>
                  <td><span class="status-pill <?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($recentAppts)): ?>
                <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:40px;">No appointments yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Recent Patients -->
        <div>
          <div class="admin-table-card">
            <div class="card-header">
              <h3>New Patients</h3>
              <a href="users.php" style="color:var(--primary); font-size:0.85rem; font-weight:600;">View All</a>
            </div>
            <table>
              <thead>
                <tr><th>Name</th><th>Joined</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recentUsers as $u): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($u['fullname']) ?></strong>
                      <div style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                    </td>
                    <td style="font-size:0.8rem; color:var(--text-muted);"><?= date('M j', strtotime($u['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

</body>
</html>
