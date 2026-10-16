<?php
// ============================================================
//  MediCare+ — Notifications
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo = getDB();
$uid = $_SESSION['user_id'];

// Mark all as read when page is opened
$pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);

$filter = $_GET['type'] ?? 'all';
$sql    = 'SELECT * FROM notifications WHERE user_id = ?';
$params = [$uid];
if ($filter !== 'all') {
    $sql    .= ' AND type = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifs = $stmt->fetchAll();

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)   return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff/3600)>1?'s':'') . ' ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff/86400)>1?'s':'') . ' ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="View your appointment reminders, medication alerts, and health notifications on MediCare+." />
  <title>Notifications | MediCare+</title>
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
      <a href="prescriptions.php"><i class="ri-medicine-bottle-line"></i> Prescriptions</a>
      <a href="notifications.php" class="active"><i class="ri-notification-3-line"></i> Notifications</a>
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
          <h1>Notifications</h1>
          <a href="notifications.php?clear=1" class="btn btn-ghost btn-small"
             style="font-weight: 600; padding: 4px 8px;" id="clear-btn">Clear</a>
        </div>

        <div style="padding-top: 16px;">
          <!-- Filter Tabs -->
          <div class="notif-tabs">
            <a href="notifications.php" class="notif-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="notifications.php?type=appointment" class="notif-tab <?= $filter === 'appointment' ? 'active' : '' ?>">Appointments</a>
            <a href="notifications.php?type=medication" class="notif-tab <?= $filter === 'medication' ? 'active' : '' ?>">Medication</a>
          </div>

          <!-- Notifications List -->
          <div class="notif-list">
            <?php if (empty($notifs)): ?>
              <div style="text-align:center; padding:60px 24px; color:var(--text-muted);">
                <i class="ri-notification-off-line" style="font-size:3rem; display:block; margin-bottom:12px;"></i>
                No notifications here.
              </div>
            <?php else: ?>
              <?php foreach ($notifs as $n): ?>
                <div class="notif-item">
                  <div class="notif-icon <?= htmlspecialchars($n['icon_color']) ?>">
                    <i class="<?= htmlspecialchars($n['icon_class']) ?>"></i>
                  </div>
                  <div class="notif-content">
                    <h4><?= htmlspecialchars($n['title']) ?></h4>
                    <p><?= htmlspecialchars($n['message']) ?></p>
                    <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
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

<?php
// Handle clear
if (isset($_GET['clear'])) {
    $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$uid]);
    header('Location: notifications.php');
    exit;
}
?>

</body>
</html>
