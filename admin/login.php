<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="../style.css" />
  <style>
    /* Admin-specific styles layered on top of main CSS */
    .admin-login-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, hsl(220,80%,10%) 0%, hsl(200,60%,18%) 100%);
      padding: 24px;
    }
    .admin-login-card {
      background: var(--bg-card);
      border-radius: 24px;
      padding: 40px 36px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.4);
    }
    .admin-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(99,102,241,.15);
      color: #818cf8;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 100px;
      margin-bottom: 16px;
      letter-spacing: .05em;
      text-transform: uppercase;
    }
  </style>
</head>
<body>
<?php
session_start();
if (!empty($_SESSION['user_id']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../php/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $pdo      = getDB();
    $stmt     = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = ['id'=>$user['id'],'fullname'=>$user['fullname'],'email'=>$user['email'],'role'=>$user['role']];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid admin credentials.';
    }
}
?>

<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div style="text-align:center; margin-bottom:28px;">
      <div style="font-size:2.5rem; color:var(--primary); margin-bottom:12px;">
        <i class="ri-shield-keyhole-line"></i>
      </div>
      <div class="admin-badge"><i class="ri-lock-line"></i> Admin Access</div>
      <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:6px;">Admin Portal</h1>
      <p style="color:var(--text-muted); font-size:0.9rem;">Sign in with your admin credentials</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><i class="ri-error-warning-line"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label for="email">Admin Email</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="admin@medicare.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="Enter password" required />
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Access Admin Panel</button>
    </form>

    <div style="text-align:center; margin-top:20px;">
      <a href="../login.php" style="color:var(--text-muted); font-size:0.85rem;">
        <i class="ri-arrow-left-line"></i> Back to Patient Login
      </a>
    </div>
  </div>
</div>

</body>
</html>
