<?php
// ============================================================
//  MediCare+ — Profile
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo        = getDB();
$uid        = $_SESSION['user_id'];
$notifCount = unreadNotifCount();

// Fetch full user profile
$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$uid]);
$profile  = $userStmt->fetch();

// Stats
$apptCount = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE user_id = ?');
$apptCount->execute([$uid]); $totalAppts = $apptCount->fetchColumn();

$docCount = $pdo->prepare('SELECT COUNT(DISTINCT doctor_id) FROM appointments WHERE user_id = ?');
$docCount->execute([$uid]); $distinctDocs = $docCount->fetchColumn();

$recCount = $pdo->prepare('SELECT COUNT(*) FROM health_records WHERE user_id = ?');
$recCount->execute([$uid]); $totalRecs = $recCount->fetchColumn();

// Handle profile update
$updateSuccess = '';
$updateError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']  ?? '');
    $phone    = trim($_POST['phone']     ?? '');
    $dob      = trim($_POST['dob']       ?? '');
    $gender   = trim($_POST['gender']    ?? '');
    $newPw    = trim($_POST['new_password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if (!$fullname) {
        $updateError = 'Full name cannot be empty.';
    } else {
        $upd = $pdo->prepare(
            'UPDATE users SET fullname=?, phone=?, dob=?, gender=? WHERE id=?'
        );
        $upd->execute([$fullname, $phone ?: null, $dob ?: null, $gender ?: null, $uid]);

        // Update session name
        $_SESSION['user']['fullname'] = $fullname;

        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                $updateError = 'New password must be at least 6 characters.';
            } elseif ($newPw !== $confirm) {
                $updateError = 'Passwords do not match.';
            } else {
                $hash = password_hash($newPw, PASSWORD_BCRYPT);
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $uid]);
            }
        }

        if (!$updateError) {
            $updateSuccess = 'Profile updated successfully!';
            // Refresh profile data
            $userStmt->execute([$uid]);
            $profile = $userStmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Manage your MediCare+ profile, settings, and account preferences." />
  <title>Profile | MediCare+</title>
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
      <a href="notifications.php"><i class="ri-notification-3-line"></i> Notifications
        <?php if ($notifCount > 0): ?><span class="sidebar-badge"><?= $notifCount ?></span><?php endif; ?>
      </a>
      <a href="profile.php" class="active"><i class="ri-user-3-line"></i> Profile</a>
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
        <div style="max-width: 700px; margin: 0 auto;">

          <!-- Profile Header -->
          <div class="profile-header">
            <div class="profile-avatar">
              <i class="ri-user-3-line"></i>
            </div>
            <h2><?= htmlspecialchars($profile['fullname']) ?></h2>
            <p><?= htmlspecialchars($profile['email']) ?></p>
            <div class="profile-stats">
              <div class="profile-stat">
                <div class="stat-num"><?= $totalAppts ?></div>
                <div class="stat-label">Visits</div>
              </div>
              <div class="profile-stat">
                <div class="stat-num"><?= $distinctDocs ?></div>
                <div class="stat-label">Doctors</div>
              </div>
              <div class="profile-stat">
                <div class="stat-num"><?= $totalRecs ?></div>
                <div class="stat-label">Records</div>
              </div>
            </div>
          </div>

          <!-- Edit Profile Form -->
          <div style="padding: 0 24px;">
            <?php if ($updateSuccess): ?>
              <div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> <?= $updateSuccess ?></div>
            <?php endif; ?>
            <?php if ($updateError): ?>
              <div class="alert alert-error"><i class="ri-error-warning-line"></i> <?= htmlspecialchars($updateError) ?></div>
            <?php endif; ?>

            <form method="POST" action="profile.php" style="margin-bottom: 24px;">
              <div class="section-header"><h3>Personal Information</h3></div>

              <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" class="form-control"
                       value="<?= htmlspecialchars($profile['fullname']) ?>" required />
              </div>
              <div class="form-group">
                <label for="email">Email (read-only)</label>
                <input type="email" id="email" class="form-control"
                       value="<?= htmlspecialchars($profile['email']) ?>" disabled />
              </div>
              <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                       value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" />
              </div>
              <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" class="form-control"
                       value="<?= htmlspecialchars($profile['dob'] ?? '') ?>" />
              </div>
              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control">
                  <option value="">Not specified</option>
                  <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= ($profile['gender'] === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="section-header" style="margin-top:16px;"><h3>Change Password</h3></div>
              <div class="form-group">
                <label for="new_password">New Password <span style="color:var(--text-muted); font-size:0.8rem;">(leave blank to keep current)</span></label>
                <input type="password" id="new_password" name="new_password" class="form-control"
                       placeholder="New password (min. 6 chars)" />
              </div>
              <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       placeholder="Confirm new password" />
              </div>

              <button type="submit" name="update_profile" value="1" class="btn btn-primary">
                Save Changes
              </button>
            </form>
          </div>

          <!-- Menu -->
          <div class="profile-menu">
            <!-- Quick Links -->
            <div class="profile-menu-group">
              <h4>Quick Links</h4>
              <a href="records.php" class="profile-menu-item">
                <div class="profile-menu-icon qa-green"><i class="ri-file-list-3-line"></i></div>
                <span class="menu-text">Health Records</span>
                <span class="menu-arrow"><i class="ri-arrow-right-s-line"></i></span>
              </a>
              <a href="prescriptions.php" class="profile-menu-item">
                <div class="profile-menu-icon qa-orange"><i class="ri-medicine-bottle-line"></i></div>
                <span class="menu-text">My Prescriptions</span>
                <span class="menu-arrow"><i class="ri-arrow-right-s-line"></i></span>
              </a>
              <a href="notifications.php" class="profile-menu-item">
                <div class="profile-menu-icon qa-pink"><i class="ri-notification-3-line"></i></div>
                <span class="menu-text">Notifications</span>
                <span class="menu-arrow"><i class="ri-arrow-right-s-line"></i></span>
              </a>
            </div>

            <!-- Logout -->
            <div class="profile-menu-group">
              <h4>Account</h4>
              <a href="logout.php" class="profile-menu-item logout-item">
                <div class="profile-menu-icon qa-red"><i class="ri-logout-box-r-line"></i></div>
                <span class="menu-text">Log Out</span>
                <span class="menu-arrow"><i class="ri-arrow-right-s-line"></i></span>
              </a>
            </div>
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
    <a href="profile.php" class="active"><i class="ri-user-3-line"></i> Profile</a>
  </nav>
</div>

</body>
</html>
