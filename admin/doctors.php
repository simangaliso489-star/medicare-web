<?php
// ============================================================
//  MediCare+ — Admin: Manage Doctors
// ============================================================
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../php/config.php';

$pdo = getDB();
$msg = '';
$err = '';

// Handle Add Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $fullname   = trim($_POST['fullname']   ?? '');
    $specialty  = trim($_POST['specialty']  ?? '');
    $hospital   = trim($_POST['hospital']   ?? '');
    $distance   = (float)($_POST['distance_km'] ?? 0);
    $fee        = (float)($_POST['consult_fee']  ?? 50);
    $status     = $_POST['status']  ?? 'offline';
    $bio        = trim($_POST['bio'] ?? '');
    $email      = trim($_POST['email']     ?? '');
    $password   = trim($_POST['password']  ?? '');

    if (!$fullname || !$specialty) {
        $err = 'Name and specialty are required.';
    } else {
        // Create user account if email+password provided
        $userId = null;
        if ($email && $password) {
            // Check dupe
            $chk = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $err = 'A user with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins  = $pdo->prepare("INSERT INTO users (fullname, email, password_hash, role) VALUES (?,?,?,'doctor')");
                $ins->execute([$fullname, $email, $hash]);
                $userId = $pdo->lastInsertId();
            }
        }
        if (!$err) {
            $ins = $pdo->prepare(
                'INSERT INTO doctors (user_id, fullname, specialty, hospital, distance_km, consult_fee, status, bio)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $ins->execute([$userId, $fullname, $specialty, $hospital ?: null, $distance ?: null, $fee, $status, $bio ?: null]);
            $msg = "Doctor <strong>$fullname</strong> added successfully!";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM doctors WHERE id=?')->execute([$id]);
    header('Location: doctors.php?deleted=1');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $doc = $pdo->prepare('SELECT status FROM doctors WHERE id=?');
    $doc->execute([$id]);
    $cur = $doc->fetchColumn();
    $new = ($cur === 'online') ? 'offline' : 'online';
    $pdo->prepare('UPDATE doctors SET status=? WHERE id=?')->execute([$new, $id]);
    header('Location: doctors.php');
    exit;
}

if (isset($_GET['deleted'])) $msg = 'Doctor removed successfully.';

$doctors = $pdo->query('SELECT * FROM doctors ORDER BY rating DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Doctors | MediCare+ Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="../style.css" />
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar { width: 260px; min-height: 100vh; background: hsl(220,80%,8%); padding: 0; display: flex; flex-direction: column; flex-shrink: 0; }
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
    .admin-table-card { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 24px; }
    .admin-table-card .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .admin-table-card .card-header h3 { font-size: 1rem; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px 20px; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing:.05em; background: var(--bg-main); }
    td { padding: 14px 20px; border-top: 1px solid var(--border); font-size: 0.875rem; }
    .status-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
    .status-online  { background: hsl(142,70%,90%); color: hsl(142,70%,30%); }
    .status-offline { background: hsl(0,0%,92%); color: hsl(0,0%,40%); }
    .action-link { color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.8rem; margin-right: 10px; }
    .action-link.danger { color: var(--error); }
    .add-form { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 28px; margin-bottom: 24px; }
    .add-form h3 { font-size: 1rem; font-weight: 700; margin-bottom: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 700px) { .form-row { grid-template-columns: 1fr; } }
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
      <a href="doctors.php" class="active"><i class="ri-nurse-line"></i> Doctors</a>
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
      <h1 style="font-size:1.25rem; font-weight:700;"><i class="ri-nurse-line" style="color:var(--primary);"></i> Manage Doctors</h1>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?><div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> <?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><i class="ri-error-warning-line"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Add Doctor Form -->
      <div class="add-form">
        <h3><i class="ri-user-add-line" style="color:var(--primary);"></i> Add New Doctor</h3>
        <form method="POST" action="doctors.php">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="fullname" class="form-control" placeholder="Dr. John Doe" required />
            </div>
            <div class="form-group">
              <label>Specialty *</label>
              <input type="text" name="specialty" class="form-control" placeholder="General Practitioner" required />
            </div>
            <div class="form-group">
              <label>Hospital / Clinic</label>
              <input type="text" name="hospital" class="form-control" placeholder="City Medical Center" />
            </div>
            <div class="form-group">
              <label>Distance (km)</label>
              <input type="number" name="distance_km" step="0.1" class="form-control" placeholder="2.5" />
            </div>
            <div class="form-group">
              <label>Consultation Fee ($)</label>
              <input type="number" name="consult_fee" step="0.01" class="form-control" value="50" />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="online">Online</option>
                <option value="offline" selected>Offline</option>
                <option value="busy">Busy</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Bio</label>
            <textarea name="bio" class="form-control" rows="2" placeholder="Short bio about the doctor..."></textarea>
          </div>
          <div style="border-top: 1px solid var(--border); padding-top: 16px; margin-top: 8px;">
            <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:12px;">
              <i class="ri-lock-line"></i> Optional: Create a login account for this doctor
            </p>
            <div class="form-row">
              <div class="form-group">
                <label>Login Email (optional)</label>
                <input type="email" name="email" class="form-control" placeholder="doctor@medicare.com" />
              </div>
              <div class="form-group">
                <label>Login Password (optional)</label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" />
              </div>
            </div>
          </div>
          <button type="submit" name="add_doctor" value="1" class="btn btn-primary">Add Doctor</button>
        </form>
      </div>

      <!-- Doctors Table -->
      <div class="admin-table-card">
        <div class="card-header">
          <h3>All Doctors (<?= count($doctors) ?>)</h3>
        </div>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Specialty</th>
              <th>Hospital</th>
              <th>Rating</th>
              <th>Fee</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($doctors as $doc): ?>
              <tr>
                <td><strong><?= htmlspecialchars($doc['fullname']) ?></strong></td>
                <td><?= htmlspecialchars($doc['specialty']) ?></td>
                <td style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($doc['hospital'] ?? '—') ?></td>
                <td><i class="ri-star-fill" style="color:var(--warning);"></i> <?= $doc['rating'] ?></td>
                <td>$<?= number_format($doc['consult_fee'], 2) ?></td>
                <td>
                  <span class="status-pill status-<?= $doc['status'] ?>"><?= ucfirst($doc['status']) ?></span>
                </td>
                <td>
                  <a href="doctors.php?toggle=<?= $doc['id'] ?>" class="action-link">
                    <?= $doc['status'] === 'online' ? 'Set Offline' : 'Set Online' ?>
                  </a>
                  <a href="doctors.php?delete=<?= $doc['id'] ?>" class="action-link danger"
                     onclick="return confirm('Delete <?= addslashes($doc['fullname']) ?>?')">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
