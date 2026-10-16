<?php
// ============================================================
//  MediCare+ — Prescriptions
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo        = getDB();
$uid        = $_SESSION['user_id'];
$notifCount = unreadNotifCount();

$filter = $_GET['filter'] ?? 'all';

$sql    = 'SELECT p.*, d.fullname AS doctor_name FROM prescriptions p
           JOIN doctors d ON p.doctor_id = d.id
           WHERE p.user_id = ?';
$params = [$uid];

if ($filter !== 'all') {
    $sql    .= ' AND p.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prescriptions = $stmt->fetchAll();

// Today's active meds for reminders
$todayStmt = $pdo->prepare(
    'SELECT * FROM prescriptions WHERE user_id = ? AND status = "active" ORDER BY created_at DESC'
);
$todayStmt->execute([$uid]);
$activeMeds = $todayStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="View your digital prescriptions and medication details on MediCare+." />
  <title>Prescriptions | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo"><i class="ri-hospital-line"></i> MediCare+</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><i class="ri-home-5-line"></i> Dashboard</a>
      <a href="doctors.php"><i class="ri-nurse-line"></i> Find Doctors</a>
      <a href="booking.php"><i class="ri-calendar-check-line"></i> Appointments</a>
      <a href="records.php"><i class="ri-folder-open-line"></i> Health Records</a>
      <a href="prescriptions.php" class="active"><i class="ri-medicine-bottle-line"></i> Prescriptions</a>
      <a href="notifications.php"><i class="ri-notification-3-line"></i> Notifications
        <?php if ($notifCount > 0): ?><span class="sidebar-badge"><?= $notifCount ?></span><?php endif; ?>
      </a>
      <a href="profile.php"><i class="ri-user-3-line"></i> Profile</a>
    </nav>
    <div style="padding: 24px 16px;">
      <a href="logout.php" class="btn btn-ghost" style="color: var(--text-secondary); width: 100%; justify-content: flex-start; gap: 12px; padding: 12px 16px;">
        <i class="ri-logout-box-r-line"></i> Log Out
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <div class="content-wrapper">
      <div class="page-content">
        <!-- Top Bar -->
        <div class="top-bar">
          <a href="records.php" class="back-btn"><i class="ri-arrow-left-line"></i></a>
          <h1>Prescriptions</h1>
          <button class="action-btn"><i class="ri-download-2-line"></i></button>
        </div>

        <div style="max-width: 800px; margin: 0 auto;">
          <!-- Filter Tabs -->
          <div class="filter-tabs">
            <?php foreach (['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'expired' => 'Expired'] as $val => $label): ?>
              <a href="prescriptions.php?filter=<?= $val ?>"
                 class="filter-tab <?= ($filter === $val) ? 'active' : '' ?>">
                <?= $label ?>
              </a>
            <?php endforeach; ?>
          </div>

          <?php if (empty($prescriptions)): ?>
            <div style="text-align:center; padding:60px 24px; color:var(--text-muted);">
              <i class="ri-medicine-bottle-line" style="font-size:3rem; display:block; margin-bottom:12px;"></i>
              No <?= $filter !== 'all' ? $filter : '' ?> prescriptions found.
            </div>
          <?php else: ?>
            <?php
            // Group by doctor prescription visit (doctor + created_at date)
            $grouped = [];
            foreach ($prescriptions as $p) {
                $key = $p['doctor_id'] . '_' . date('Y-m-d', strtotime($p['created_at']));
                $grouped[$key][] = $p;
            }
            foreach ($grouped as $group):
                $first = $group[0];
                $statusClass = [
                    'active'    => 'active-rx',
                    'completed' => 'completed-rx',
                    'expired'   => 'expired-rx',
                ][$first['status']] ?? 'active-rx';
                $statusBadge = [
                    'active'    => 'status-active',
                    'completed' => 'status-completed',
                    'expired'   => 'status-expired',
                ][$first['status']] ?? 'status-active';
            ?>
              <div class="prescription-card <?= $statusClass ?>">
                <div class="prescription-header">
                  <h4><?= htmlspecialchars($first['doctor_name']) ?></h4>
                  <span class="prescription-status <?= $statusBadge ?>"><?= ucfirst($first['status']) ?></span>
                </div>
                <div class="prescription-meds">
                  <?php foreach ($group as $med): ?>
                    <div class="med-item">
                      <span class="med-icon"><i class="ri-capsule-line"></i></span>
                      <div class="med-info">
                        <h5><?= htmlspecialchars($med['medication']) ?>
                          <?php if ($med['dosage']): ?>
                            <span style="font-weight:400; color:var(--text-muted);"> — <?= htmlspecialchars($med['dosage']) ?></span>
                          <?php endif; ?>
                        </h5>
                        <p><?= htmlspecialchars($med['frequency'] ?? '') ?>
                          <?php if ($med['notes']): ?>
                            &middot; <em><?= htmlspecialchars($med['notes']) ?></em>
                          <?php endif; ?>
                        </p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="prescription-footer">
                  <?php if ($first['start_date'] && $first['end_date']): ?>
                    <span><i class="ri-calendar-line"></i>
                      <?= date('M j', strtotime($first['start_date'])) ?> – <?= date('M j, Y', strtotime($first['end_date'])) ?>
                    </span>
                    <span><?= (new DateTime($first['start_date']))->diff(new DateTime($first['end_date']))->days ?> days</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Today's Reminders (active meds only) -->
          <?php if (!empty($activeMeds)): ?>
            <div class="section-header" style="margin-top:24px;">
              <h3><i class="ri-alarm-line" style="color: var(--primary);"></i> Today's Reminders</h3>
            </div>
            <div style="padding: 0 24px;">
              <?php foreach ($activeMeds as $i => $med): ?>
                <div class="tip-card" <?= $i > 0 ? 'style="margin-top:10px;"' : '' ?>>
                  <div class="tip-icon qa-<?= ['green','blue','orange','purple'][$i % 4] ?>">
                    <i class="ri-capsule-line"></i>
                  </div>
                  <div class="tip-content">
                    <h4><?= htmlspecialchars($med['medication']) ?></h4>
                    <p><?= htmlspecialchars($med['frequency'] ?? '') ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </main>

  <!-- Bottom Navigation -->
  <nav class="bottom-nav">
    <a href="dashboard.php"><i class="ri-home-5-line"></i> Home</a>
    <a href="doctors.php"><i class="ri-nurse-line"></i> Doctors</a>
    <a href="booking.php"><i class="ri-calendar-check-line"></i> Book</a>
    <a href="records.php"><i class="ri-folder-open-line"></i> Records</a>
    <a href="profile.php"><i class="ri-user-3-line"></i> Profile</a>
  </nav>
</div>

</body>
</html>
