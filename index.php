<?php
// ============================================================
//  MediCare+ — Splash / Landing Page
// ============================================================
session_start();
// If already logged in, send to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="MediCare+ — Your trusted online healthcare companion. Book appointments, consult doctors, and manage your health records." />
  <title>MediCare+ | Online Healthcare</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <div class="splash-screen">
    <!-- Top Section -->
    <div class="splash-top">
      <div class="splash-logo">
        <i class="ri-hospital-line"></i>
      </div>
      <h1>MediCare+</h1>
      <p>Your health, our priority. Access quality healthcare anytime, anywhere from your device.</p>
    </div>

    <!-- Bottom Section -->
    <div class="splash-bottom">
      <div class="splash-features">
        <div class="splash-feature">
          <div class="feature-icon text-primary bg-primary-50">
            <i class="ri-user-search-line"></i>
          </div>
          <p>Find Doctors</p>
        </div>
        <div class="splash-feature">
          <div class="feature-icon text-success bg-success-light">
            <i class="ri-calendar-check-line"></i>
          </div>
          <p>Book Appointments</p>
        </div>
        <div class="splash-feature">
          <div class="feature-icon text-info bg-info-light">
            <i class="ri-chat-voice-line"></i>
          </div>
          <p>Online Consult</p>
        </div>
        <div class="splash-feature">
          <div class="feature-icon text-warning bg-warning-light">
            <i class="ri-file-list-3-line"></i>
          </div>
          <p>Health Records</p>
        </div>
      </div>

      <a href="login.php" class="btn btn-primary">Get Started</a>
      <a href="register.php" class="btn btn-outline">Create Account</a>

      <p class="splash-footer-text">
        Already have an account? <a href="login.php">Sign In</a>
      </p>
    </div>
  </div>

</body>
</html>
