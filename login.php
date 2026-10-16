<?php
// ============================================================
//  MediCare+ — Login Page
// ============================================================
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/php/config.php';

$error = '';
$success = '';

// Check for success message from register
if (!empty($_GET['registered'])) {
    $success = 'Account created! Please sign in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = [
                'id'       => $user['id'],
                'fullname' => $user['fullname'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ];

            // Role-based redirect
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Sign in to your MediCare+ account to access healthcare services." />
  <title>Login | MediCare+</title>
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
      <h1>Welcome Back</h1>
      <p>Sign in to continue to MediCare+</p>
    </div>

    <!-- Form -->
    <div class="auth-body">

      <?php if ($error): ?>
        <div class="alert alert-error" id="login-error">
          <i class="ri-error-warning-line"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success" id="login-success">
          <i class="ri-checkbox-circle-line"></i> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form id="login-form" method="POST" action="login.php">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="Enter your email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Enter your password" required />
        </div>

        <div class="remember-row">
          <div class="checkbox-group">
            <input type="checkbox" id="remember" name="remember" />
            <label for="remember">Remember me</label>
          </div>
          <a href="#" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary" id="login-btn">Sign In</button>
      </form>

      <div class="divider">or continue with</div>

      <div class="social-btns">
        <button class="btn btn-ghost" style="color: #4285F4;"><i class="ri-google-fill"></i></button>
        <button class="btn btn-ghost" style="color: #1877F2;"><i class="ri-facebook-circle-fill"></i></button>
        <button class="btn btn-ghost" style="color: #000000;"><i class="ri-apple-fill"></i></button>
      </div>
    </div>

    <!-- Footer -->
    <div class="auth-footer">
      Don't have an account? <a href="register.php">Sign Up</a>
    </div>
  </div>

</body>
</html>
