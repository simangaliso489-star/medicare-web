<?php
// ============================================================
//  MediCare+ — Health Records
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo        = getDB();
$uid        = $_SESSION['user_id'];
$notifCount = unreadNotifCount();

// Counts
$counts = $pdo->prepare(
    'SELECT
       COUNT(*) AS total,
       SUM(record_type = "prescription") AS prescriptions,
       SUM(record_type IN ("lab","xray")) AS lab_results,
       SUM(record_type = "consultation") AS visits
     FROM health_records WHERE user_id = ?'
);
$counts->execute([$uid]);
$stats = $counts->fetch();

// Records list
$recStmt = $pdo->prepare(
    'SELECT * FROM health_records WHERE user_id = ? ORDER BY created_at DESC'
);
$recStmt->execute([$uid]);
$records = $recStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="View and manage your medical records securely on MediCare+." />
  <title>Medical Records | MediCare+</title>
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
      <a href="records.php" class="active"><i class="ri-folder-open-line"></i> Health Records</a>
      <a href="prescriptions.php"><i class="ri-medicine-bottle-line"></i> Prescriptions</a>
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
          <a href="dashboard.php" class="back-btn"><i class="ri-arrow-left-line"></i></a>
          <h1>Medical Records</h1>
          <button class="action-btn"><i class="ri-add-line"></i></button>
        </div>

        <!-- Summary Stats -->
        <div class="records-summary">
          <div class="record-stat">
            <div class="stat-icon text-primary bg-primary-50"><i class="ri-file-list-3-line"></i></div>
            <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
            <div class="stat-label">Total Records</div>
          </div>
          <div class="record-stat">
            <div class="stat-icon text-success bg-success-light"><i class="ri-medicine-bottle-line"></i></div>
            <div class="stat-value"><?= $stats['prescriptions'] ?? 0 ?></div>
            <div class="stat-label">Prescriptions</div>
          </div>
          <div class="record-stat">
            <div class="stat-icon text-info bg-info-light"><i class="ri-microscope-line"></i></div>
            <div class="stat-value"><?= $stats['lab_results'] ?? 0 ?></div>
            <div class="stat-label">Lab Results</div>
          </div>
          <div class="record-stat">
            <div class="stat-icon text-warning bg-warning-light"><i class="ri-calendar-check-line"></i></div>
            <div class="stat-value"><?= $stats['visits'] ?? 0 ?></div>
            <div class="stat-label">Visits</div>
          </div>
        </div>

        <!-- Records List -->
        <div class="section-header">
          <h3>Recent Records</h3>
          <a href="#">Filter</a>
        </div>

        <div class="records-list">
          <?php if (empty($records)): ?>
            <div style="text-align:center; padding:60px 24px; color:var(--text-muted);">
              <i class="ri-folder-open-line" style="font-size:3rem; display:block; margin-bottom:12px;"></i>
              No health records yet.
            </div>
          <?php else: ?>
            <?php foreach ($records as $rec): ?>
              <a href="#" class="record-item">
                <div class="record-icon <?= htmlspecialchars($rec['icon_color']) ?>">
                  <i class="<?= htmlspecialchars($rec['icon_class']) ?>"></i>
                </div>
                <div class="record-info">
                  <h4><?= htmlspecialchars($rec['title']) ?></h4>
                  <p><?= htmlspecialchars($rec['description'] ?? '') ?> &middot; <?= date('M j, Y', strtotime($rec['created_at'])) ?></p>
                </div>
                <span class="record-arrow"><i class="ri-arrow-right-s-line"></i></span>
              </a>
            <?php endforeach; ?>
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
    <a href="records.php" class="active"><i class="ri-folder-open-line"></i> Records</a>
    <a href="profile.php"><i class="ri-user-3-line"></i> Profile</a>
  </nav>
</div>

</body>
</html>
