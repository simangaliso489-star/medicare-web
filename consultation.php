<?php
// ============================================================
//  MediCare+ — Consultation (Chat)
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo        = getDB();
$uid        = $_SESSION['user_id'];
$notifCount = unreadNotifCount();

// Get doctor from query param (default to first doctor)
$doctorId = (int)($_GET['doctor_id'] ?? 1);
$docStmt  = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
$docStmt->execute([$doctorId]);
$doctor   = $docStmt->fetch();

if (!$doctor) {
    header('Location: doctors.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Chat with your doctor in real-time on MediCare+." />
  <title>Consultation | MediCare+</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body style="background: var(--bg-white);">

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
      <a href="profile.php"><i class="ri-user-3-line"></i> Profile</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <div class="content-wrapper" style="height: 100vh; position: relative;">
      <!-- Top Bar -->
      <div class="top-bar">
        <a href="dashboard.php" class="back-btn"><i class="ri-arrow-left-line"></i></a>
        <div style="text-align: center;">
          <h1 style="font-size: 1.05rem;"><?= htmlspecialchars($doctor['fullname']) ?></h1>
          <p style="font-size: 0.75rem; color: var(--success); font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 4px;">
            <span style="width: 6px; height: 6px; background: var(--success); border-radius: 50%; display: inline-block;"></span>
            <?= $doctor['status'] === 'online' ? 'Online' : 'Away' ?>
          </p>
        </div>
        <div style="display: flex; gap: 8px;">
          <button class="action-btn"><i class="ri-phone-line"></i></button>
          <button class="action-btn"><i class="ri-vidicon-line"></i></button>
        </div>
      </div>

      <!-- Chat Messages -->
      <div class="chat-container" id="chat-container">
        <div class="chat-date">Today, <?= date('M j, Y') ?></div>

        <div class="chat-bubble received">
          Hello! I'm <?= htmlspecialchars($doctor['fullname']) ?>, your <?= htmlspecialchars($doctor['specialty']) ?>. How can I help you today?
          <div class="chat-time"><?= date('g:i A') ?></div>
        </div>

        <div id="chat-messages"></div>
      </div>

      <!-- Chat Input Bar -->
      <div class="chat-input-bar">
        <div class="content-wrapper" style="display: flex; gap: 10px; align-items: center;">
          <button class="chat-attach-btn"><i class="ri-attachment-2"></i></button>
          <input type="text" id="chat-input" placeholder="Type a message..." />
          <button class="chat-send-btn" id="send-btn" onclick="sendMessage()">
            <i class="ri-send-plane-fill"></i>
          </button>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
const chatContainer = document.getElementById('chat-container');
const chatInput     = document.getElementById('chat-input');

// Doctor auto-replies (simulated)
const autoReplies = [
  "I understand. Can you describe when it started?",
  "Thank you for the information. Have you experienced this before?",
  "I see. Are you on any current medications?",
  "Let me note that down. Do you have any allergies we should know about?",
  "Based on what you've described, I recommend scheduling an in-person visit. I'll send you a prescription shortly.",
  "Please stay hydrated and get plenty of rest. Contact me if symptoms worsen.",
];
let replyIndex = 0;

function sendMessage() {
  const text = chatInput.value.trim();
  if (!text) return;

  // Add sent bubble
  const sent = document.createElement('div');
  sent.className = 'chat-bubble sent';
  sent.innerHTML = `${escapeHtml(text)} <div class="chat-time">${now()}</div>`;
  document.getElementById('chat-messages').appendChild(sent);

  chatInput.value = '';
  scrollToBottom();

  // Simulate doctor reply
  setTimeout(() => {
    const reply = document.createElement('div');
    reply.className = 'chat-bubble received';
    reply.innerHTML = `${escapeHtml(autoReplies[replyIndex % autoReplies.length])} <div class="chat-time">${now()}</div>`;
    document.getElementById('chat-messages').appendChild(reply);
    replyIndex++;
    scrollToBottom();
  }, 1200 + Math.random() * 800);
}

chatInput.addEventListener('keypress', e => {
  if (e.key === 'Enter') sendMessage();
});

function scrollToBottom() {
  chatContainer.scrollTop = chatContainer.scrollHeight;
}

function now() {
  return new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function escapeHtml(text) {
  return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

</body>
</html>
