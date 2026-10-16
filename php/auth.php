<?php
// ============================================================
//  MediCare+ — Session Auth Guard
//  Include at the top of every protected page:
//    require_once __DIR__ . '/php/auth.php';
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in, redirect to login page
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Helper: get the logged-in user array (cached in session)
function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

// Helper: get unread notification count for nav badge
function unreadNotifCount(): int {
    require_once __DIR__ . '/config.php';
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}
