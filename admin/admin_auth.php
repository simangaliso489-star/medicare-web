<?php
// ============================================================
//  MediCare+ — Admin Auth Guard
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

function adminUser(): array { return $_SESSION['user'] ?? []; }
