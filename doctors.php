<?php
// ============================================================
//  MediCare+ — Find Doctors
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo        = getDB();
$search     = trim($_GET['search']    ?? '');
$specialty  = trim($_GET['specialty'] ?? '');
$notifCount = unreadNotifCount();

// Build query
$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(d.fullname LIKE ? OR d.specialty LIKE ? OR d.hospital LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($specialty !== '' && $specialty !== 'All') {
    $where[]  = 'd.specialty LIKE ?';
    $params[] = "%$specialty%";
}

$sql = 'SELECT d.* FROM doctors d';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY d.rating DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Distinct specialties for filter tabs
$specStmt = $pdo->query('SELECT DISTINCT specialty FROM doctors ORDER BY specialty');
$specialties = $specStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Search and find top healthcare professionals on MediCare+." />
  <title>Find Doctors | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<div class="app-layout">
  
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <i class="ri-hospital-line"></i>
      MediCare+
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><i class="ri-home-5-line"></i> Dashboard</a>
      <a href="doctors.php" class="active"><i class="ri-nurse-line"></i> Find Doctors</a>
      <a href="booking.php"><i class="ri-calendar-check-line"></i> Appointments</a>
      <a href="records.php"><i class="ri-folder-open-line"></i> Health Records</a>
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
          <a href="dashboard.php" class="back-btn">
            <i class="ri-arrow-left-line"></i>
          </a>
          <h1>Find Doctors</h1>
          <button class="action-btn" id="filter-btn">
            <i class="ri-filter-3-line"></i>
          </button>
        </div>

        <!-- Search -->
        <form method="GET" action="doctors.php" style="padding: 14px 24px;">
          <div class="form-control" style="display: flex; align-items: center; gap: 8px;">
            <i class="ri-search-line" style="color: var(--text-muted);"></i>
            <input type="text" name="search" id="search-box"
                   placeholder="Search by name or specialty..."
                   value="<?= htmlspecialchars($search) ?>"
                   style="flex: 1; border: none; outline: none; background: transparent; font-size: 0.938rem; color: var(--text-primary);" />
            <?php if ($search): ?>
              <a href="doctors.php" style="color: var(--text-muted);"><i class="ri-close-line"></i></a>
            <?php endif; ?>
          </div>
        </form>

        <!-- Filter Tabs -->
        <div class="filter-tabs" id="filter-tabs">
          <a href="doctors.php" class="filter-tab <?= ($specialty === '' || $specialty === 'All') ? 'active' : '' ?>">All</a>
          <?php foreach ($specialties as $spec): ?>
            <a href="doctors.php?specialty=<?= urlencode($spec) ?>"
               class="filter-tab <?= ($specialty === $spec) ? 'active' : '' ?>">
              <?= htmlspecialchars($spec) ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Doctor List -->
        <div class="doctor-list" style="margin-top: 8px;">
          <?php if (empty($doctors)): ?>
            <div style="text-align:center; padding: 60px 24px; color: var(--text-muted);">
              <i class="ri-user-search-line" style="font-size: 3rem; margin-bottom: 12px; display:block;"></i>
              <p>No doctors found matching "<strong><?= htmlspecialchars($search ?: $specialty) ?></strong>".</p>
              <a href="doctors.php" class="btn btn-primary" style="margin-top:16px; display:inline-flex;">Clear Search</a>
            </div>
          <?php else: ?>
            <?php foreach ($doctors as $doc): ?>
              <a href="booking.php?doctor_id=<?= $doc['id'] ?>" class="doctor-list-card">
                <div class="doc-list-avatar">
                  <i class="<?= htmlspecialchars($doc['icon_class']) ?>"></i>
                </div>
                <div class="doc-list-info">
                  <h4><?= htmlspecialchars($doc['fullname']) ?></h4>
                  <p class="doc-specialty"><?= htmlspecialchars($doc['specialty']) ?></p>
                  <div class="doc-meta">
                    <span><i class="ri-star-fill" style="color: var(--warning);"></i> <?= $doc['rating'] ?></span>
                    <?php if ($doc['distance_km']): ?>
                      <span><i class="ri-map-pin-line"></i> <?= $doc['distance_km'] ?> km</span>
                    <?php endif; ?>
                    <span class="badge badge-<?= $doc['status'] === 'online' ? 'online' : 'offline' ?>">
                      <?= ucfirst($doc['status']) ?>
                    </span>
                    <span style="font-size:0.8rem; color: var(--text-muted);">$<?= number_format($doc['consult_fee'], 2) ?></span>
                  </div>
                </div>
                <div class="doc-list-book">
                  <span class="btn btn-primary btn-small">Book</span>
                </div>
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
    <a href="doctors.php" class="active"><i class="ri-nurse-line"></i> Doctors</a>
    <a href="booking.php"><i class="ri-calendar-check-line"></i> Book</a>
    <a href="records.php"><i class="ri-folder-open-line"></i> Records</a>
    <a href="profile.php"><i class="ri-user-3-line"></i> Profile</a>
  </nav>

</div>

<script>
// Live search on typing (debounced)
let searchTimer;
document.getElementById('search-box').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  searchTimer = setTimeout(() => {
    const url = new URL(window.location.href);
    if (q) { url.searchParams.set('search', q); }
    else    { url.searchParams.delete('search'); }
    window.location.href = url.toString();
  }, 600);
});
</script>

</body>
</html>
