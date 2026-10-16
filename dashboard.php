<?php
// ============================================================
//  MediCare+ — Dashboard
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo  = getDB();
$user = currentUser();
$uid  = $_SESSION['user_id'];

// Greeting based on time
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

// Upcoming appointments (next 2)
$apptStmt = $pdo->prepare(
    'SELECT a.*, d.fullname AS doctor_name, d.specialty
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.user_id = ? AND a.appt_date >= CURDATE() AND a.status != "cancelled"
     ORDER BY a.appt_date ASC, a.appt_time ASC
     LIMIT 2'
);
$apptStmt->execute([$uid]);
$upcomingAppts = $apptStmt->fetchAll();

// Top doctors (by rating)
$docStmt = $pdo->query(
    'SELECT * FROM doctors ORDER BY rating DESC, reviews_count DESC LIMIT 6'
);
$topDoctors = $docStmt->fetchAll();

// Unread notification count
$notifCount = unreadNotifCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="MediCare+ Dashboard — Manage your appointments, find doctors, and access health services." />
  <title>Dashboard | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<div class="app-layout">
  
  <!-- Sidebar for Desktop -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <i class="ri-hospital-line"></i>
      MediCare+
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="active"><i class="ri-home-5-line"></i> Dashboard</a>
      <a href="doctors.php"><i class="ri-nurse-line"></i> Find Doctors</a>
      <a href="booking.php"><i class="ri-calendar-check-line"></i> Appointments</a>
      <a href="records.php"><i class="ri-folder-open-line"></i> Health Records</a>
      <a href="prescriptions.php"><i class="ri-medicine-bottle-line"></i> Prescriptions</a>
      <a href="notifications.php"><i class="ri-notification-3-line"></i> Notifications
        <?php if ($notifCount > 0): ?>
          <span class="sidebar-badge"><?= $notifCount ?></span>
        <?php endif; ?>
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
        <!-- Dashboard Header -->
        <div class="dash-header">
          <div class="dash-header-top">
            <div class="user-info">
              <div class="user-avatar">
                <i class="ri-user-line"></i>
              </div>
              <div class="user-greeting">
                <span><?= htmlspecialchars($greeting) ?></span>
                <h2><?= htmlspecialchars(explode(' ', $user['fullname'])[0]) ?></h2>
              </div>
            </div>
            <div class="header-actions">
              <a href="notifications.php" class="header-action-btn">
                <i class="ri-notification-3-line"></i>
                <?php if ($notifCount > 0): ?>
                  <span class="notification-dot"></span>
                <?php endif; ?>
              </a>
            </div>
          </div>

          <div class="search-bar">
            <i class="ri-search-line"></i>
            <input type="text" placeholder="Search doctors, services..." id="search-input" />
          </div>
        </div>

        <div class="dashboard-grid">
          <div class="dashboard-main">
            <!-- Quick Actions -->
            <div class="quick-actions">
              <a href="doctors.php" class="quick-action">
                <div class="qa-icon qa-blue"><i class="ri-nurse-line"></i></div>
                <span>Doctors</span>
              </a>
              <a href="booking.php" class="quick-action">
                <div class="qa-icon qa-green"><i class="ri-calendar-check-line"></i></div>
                <span>Book</span>
              </a>
              <a href="consultation.php" class="quick-action">
                <div class="qa-icon qa-purple"><i class="ri-chat-voice-line"></i></div>
                <span>Consult</span>
              </a>
              <a href="prescriptions.php" class="quick-action">
                <div class="qa-icon qa-orange"><i class="ri-medicine-bottle-line"></i></div>
                <span>Pharmacy</span>
              </a>
              <a href="records.php" class="quick-action">
                <div class="qa-icon qa-pink"><i class="ri-file-list-3-line"></i></div>
                <span>Records</span>
              </a>
              <a href="#" class="quick-action">
                <div class="qa-icon qa-teal"><i class="ri-microscope-line"></i></div>
                <span>Lab Tests</span>
              </a>
              <a href="#" class="quick-action">
                <div class="qa-icon qa-red"><i class="ri-hospital-line"></i></div>
                <span>Emergency</span>
              </a>
              <a href="profile.php" class="quick-action">
                <div class="qa-icon qa-slate"><i class="ri-settings-4-line"></i></div>
                <span>Settings</span>
              </a>
            </div>

            <!-- Top Doctors -->
            <div>
              <div class="section-header">
                <h3>Top Doctors</h3>
                <a href="doctors.php">See All</a>
              </div>
              <div class="doctor-scroll">
                <?php foreach ($topDoctors as $doc): ?>
                  <a href="booking.php?doctor_id=<?= $doc['id'] ?>" class="doctor-card">
                    <div class="doc-avatar"><i class="<?= htmlspecialchars($doc['icon_class']) ?>"></i></div>
                    <h4><?= htmlspecialchars($doc['fullname']) ?></h4>
                    <p class="specialty"><?= htmlspecialchars($doc['specialty']) ?></p>
                    <p class="rating"><i class="ri-star-fill"></i> <?= $doc['rating'] ?></p>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="dashboard-side">
            <!-- Upcoming Appointments -->
            <div>
              <div class="section-header">
                <h3>Upcoming Appointments</h3>
                <a href="booking.php">See All</a>
              </div>
              <?php if (empty($upcomingAppts)): ?>
                <div class="appointment-card" style="text-align:center; color:var(--text-muted); padding: 24px;">
                  <i class="ri-calendar-line" style="font-size:2rem; margin-bottom:8px; display:block;"></i>
                  No upcoming appointments.<br>
                  <a href="booking.php" style="color:var(--primary); font-weight:600;">Book one now →</a>
                </div>
              <?php else: ?>
                <?php foreach ($upcomingAppts as $appt): ?>
                  <div class="appointment-card">
                    <div class="appt-doctor">
                      <div class="appt-avatar">
                        <i class="ri-user-smile-line"></i>
                      </div>
                      <div class="appt-doctor-info">
                        <h4><?= htmlspecialchars($appt['doctor_name']) ?></h4>
                        <p><?= htmlspecialchars($appt['specialty']) ?></p>
                      </div>
                    </div>
                    <div class="appt-details">
                      <div class="appt-detail">
                        <i class="ri-calendar-event-line"></i>
                        <span><?= date('M j, Y', strtotime($appt['appt_date'])) ?></span>
                      </div>
                      <div class="appt-detail">
                        <i class="ri-time-line"></i>
                        <span><?= date('g:i A', strtotime($appt['appt_time'])) ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- Health Tips -->
            <div>
              <div class="section-header">
                <h3>Health Tips</h3>
                <a href="#">See All</a>
              </div>
              <div class="tips-list">
                <div class="tip-card">
                  <div class="tip-icon qa-blue"><i class="ri-drop-line"></i></div>
                  <div class="tip-content">
                    <h4>Stay Hydrated</h4>
                    <p>Drink at least 8 glasses of water daily.</p>
                  </div>
                </div>
                <div class="tip-card">
                  <div class="tip-icon qa-orange"><i class="ri-run-line"></i></div>
                  <div class="tip-content">
                    <h4>Exercise Regularly</h4>
                    <p>30 minutes of daily exercise boosts immunity.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Bottom Navigation (Mobile Only) -->
  <nav class="bottom-nav">
    <a href="dashboard.php" class="active">
      <i class="ri-home-5-line"></i>
      Home
    </a>
    <a href="doctors.php">
      <i class="ri-nurse-line"></i>
      Doctors
    </a>
    <a href="booking.php">
      <i class="ri-calendar-check-line"></i>
      Book
    </a>
    <a href="records.php">
      <i class="ri-folder-open-line"></i>
      Records
    </a>
    <a href="profile.php">
      <i class="ri-user-3-line"></i>
      Profile
    </a>
  </nav>

</div>

<script>
// Live search to filter top doctors scroll
document.getElementById('search-input').addEventListener('input', function() {
  const q = this.value.trim().toLowerCase();
  if (q.length > 1) {
    window.location.href = 'doctors.php?search=' + encodeURIComponent(q);
  }
});
</script>

</body>
</html>
