<?php
// ============================================================
//  MediCare+ — Register Page
// ============================================================
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/php/config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname  = trim($_POST['fullname']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $dob       = trim($_POST['dob']       ?? '');
    $gender    = trim($_POST['gender']    ?? '');
    $password  = trim($_POST['password']  ?? '');
    $confirm   = trim($_POST['confirm']   ?? '');
    $terms     = isset($_POST['terms']);

    // Basic validation
    if (!$fullname || !$email || !$password || !$confirm) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms & Conditions.';
    } else {
        $pdo = getDB();

        // Check for duplicate email
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists. <a href="login.php">Sign in instead?</a>';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare(
                'INSERT INTO users (fullname, email, phone, dob, gender, password_hash, role)
                 VALUES (?, ?, ?, ?, ?, ?, "patient")'
            );
            $ins->execute([
                $fullname,
                $email,
                $phone ?: null,
                $dob   ?: null,
                $gender ?: null,
                $hash,
            ]);

            // Auto-login
            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user']    = [
                'id'       => $userId,
                'fullname' => $fullname,
                'email'    => $email,
                'role'     => 'patient',
            ];
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Create your MediCare+ account and start accessing quality healthcare services." />
  <title>Register | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <div class="auth-page">
    <!-- Header -->
    <div class="auth-header">
      <a href="index.php" class="auth-back">
        <i class="ri-arrow-left-line"></i>
      </a>
      <h1>Create Account</h1>
      <p>Join MediCare+ for better healthcare</p>
    </div>

    <!-- Form -->
    <div class="auth-body">

      <?php if ($error): ?>
        <div class="alert alert-error" id="register-error">
          <i class="ri-error-warning-line"></i> <?= $error ?>
        </div>
      <?php endif; ?>

      <form id="register-form" method="POST" action="register.php">
        <div class="form-group">
          <label for="fullname">Full Name <span style="color:var(--error)">*</span></label>
          <input type="text" id="fullname" name="fullname" class="form-control"
                 placeholder="Enter your full name"
                 value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label for="email">Email Address <span style="color:var(--error)">*</span></label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="Enter your email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" class="form-control"
                 placeholder="Enter your phone number"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
        </div>

        <div class="form-group">
          <label for="dob">Date of Birth</label>
          <input type="date" id="dob" name="dob" class="form-control"
                 value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" />
        </div>

        <div class="form-group">
          <label for="gender">Gender</label>
          <select id="gender" name="gender" class="form-control">
            <option value="" disabled <?= empty($_POST['gender']) ? 'selected' : '' ?>>Select gender</option>
            <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
            <option value="other"  <?= (($_POST['gender'] ?? '') === 'other')  ? 'selected' : '' ?>>Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Password <span style="color:var(--error)">*</span></label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Create a password (min. 6 chars)" required />
        </div>

        <div class="form-group">
          <label for="confirm">Confirm Password <span style="color:var(--error)">*</span></label>
          <input type="password" id="confirm" name="confirm" class="form-control"
                 placeholder="Confirm your password" required />
        </div>

        <div class="checkbox-group" style="margin-bottom: 24px;">
          <input type="checkbox" id="terms" name="terms"
                 <?= isset($_POST['terms']) ? 'checked' : '' ?> />
          <label for="terms">I agree to the <a href="#" class="forgot-link">Terms &amp; Conditions</a></label>
        </div>

        <button type="submit" class="btn btn-primary" id="register-btn">Create Account</button>
      </form>
    </div>

    <!-- Footer -->
    <div class="auth-footer">
      Already have an account? <a href="login.php">Sign In</a>
    </div>
  </div>

</body>
</html>
