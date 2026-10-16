<?php
// ============================================================
//  MediCare+ — Book Appointment
// ============================================================
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/config.php';

$pdo        = getDB();
$uid        = $_SESSION['user_id'];
$notifCount = unreadNotifCount();

// ------ Load doctor ------
$doctorId = (int)($_GET['doctor_id'] ?? $_POST['doctor_id'] ?? 1);
$docStmt  = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
$docStmt->execute([$doctorId]);
$doctor   = $docStmt->fetch();

if (!$doctor) {
    header('Location: doctors.php');
    exit;
}

// ------ Generate next 7 days ------
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = [
        'date'    => date('Y-m-d', strtotime("+$i days")),
        'day'     => date('D',     strtotime("+$i days")),
        'num'     => date('d',     strtotime("+$i days")),
    ];
}

$selectedDate = $_POST['appt_date'] ?? $_GET['date'] ?? $days[0]['date'];

// ------ Booked slots for selected date ------
$bookedStmt = $pdo->prepare(
    'SELECT appt_time FROM appointments WHERE doctor_id = ? AND appt_date = ? AND status != "cancelled"'
);
$bookedStmt->execute([$doctorId, $selectedDate]);
$bookedSlots = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);

// All possible time slots
$allSlots = [
    '08:00','08:30','09:00','09:30','10:00','10:30',
    '11:00','11:00','14:00','14:30','15:00','15:30',
];
$allSlots = array_unique($allSlots);

// ------ Handle form submission ------
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $apptDate    = $_POST['appt_date']    ?? '';
    $apptTime    = $_POST['appt_time']    ?? '';
    $consultType = $_POST['consult_type'] ?? 'in-person';
    $reason      = trim($_POST['reason']  ?? '');

    if (!$apptDate || !$apptTime) {
        $error = 'Please select a date and time slot.';
    } else {
        // Check slot still available
        $chk = $pdo->prepare(
            'SELECT id FROM appointments WHERE doctor_id=? AND appt_date=? AND appt_time=? AND status != "cancelled"'
        );
        $chk->execute([$doctorId, $apptDate, $apptTime . ':00']);
        if ($chk->fetch()) {
            $error = 'That time slot was just taken. Please choose another.';
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO appointments (user_id, doctor_id, appt_date, appt_time, consult_type, reason, fee, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $ins->execute([$uid, $doctorId, $apptDate, $apptTime . ':00', $consultType, $reason, $doctor['consult_fee']]);

            // Add a notification
            $notifIns = $pdo->prepare(
                'INSERT INTO notifications (user_id, title, message, type, icon_class, icon_color)
                 VALUES (?, ?, ?, "appointment", "ri-calendar-check-line", "qa-green")'
            );
            $notifIns->execute([
                $uid,
                'Appointment Booked',
                'Your appointment with ' . $doctor['fullname'] . ' on ' . date('M j, Y', strtotime($apptDate)) . ' at ' . date('g:i A', strtotime($apptTime)) . ' has been booked.',
            ]);

            header('Location: dashboard.php?booked=1');
            exit;
        }
    }
}

$fee          = $doctor['consult_fee'];
$serviceCharge = 5.00;
$discount      = 5.00;
$total         = $fee + $serviceCharge - $discount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Book an appointment with your doctor on MediCare+." />
  <title>Book Appointment | MediCare+</title>
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
      <a href="doctors.php"><i class="ri-nurse-line"></i> Find Doctors</a>
      <a href="booking.php" class="active"><i class="ri-calendar-check-line"></i> Appointments</a>
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
          <a href="doctors.php" class="back-btn"><i class="ri-arrow-left-line"></i></a>
          <h1>Book Appointment</h1>
          <button class="action-btn"><i class="ri-heart-3-line"></i></button>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-error" style="margin: 0 24px 16px;">
            <i class="ri-error-warning-line"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <div style="max-width: 800px; margin: 0 auto;">
          <form method="POST" action="booking.php?doctor_id=<?= $doctorId ?>">
            <input type="hidden" name="doctor_id"  value="<?= $doctorId ?>">
            <input type="hidden" name="appt_date"  id="hidden-date" value="<?= htmlspecialchars($selectedDate) ?>">
            <input type="hidden" name="appt_time"  id="hidden-time" value="<?= htmlspecialchars($_POST['appt_time'] ?? '') ?>">
            <input type="hidden" name="consult_type" id="hidden-consult" value="<?= htmlspecialchars($_POST['consult_type'] ?? 'in-person') ?>">

            <!-- Doctor Info -->
            <div class="booking-doctor">
              <div class="booking-doc-avatar">
                <i class="<?= htmlspecialchars($doctor['icon_class']) ?>"></i>
              </div>
              <div class="booking-doc-info">
                <h3><?= htmlspecialchars($doctor['fullname']) ?></h3>
                <p class="specialty"><?= htmlspecialchars($doctor['specialty']) ?></p>
                <p class="location"><i class="ri-map-pin-line"></i>
                  <?= htmlspecialchars($doctor['hospital']) ?>
                  <?php if ($doctor['distance_km']): ?>, <?= $doctor['distance_km'] ?> km<?php endif; ?>
                </p>
                <div class="booking-doc-stats">
                  <span class="stat-rating"><i class="ri-star-fill"></i> <?= $doctor['rating'] ?></span>
                  <span class="stat-reviews"><?= $doctor['reviews_count'] ?>+ Reviews</span>
                </div>
              </div>
            </div>

            <!-- Select Date -->
            <div class="booking-section">
              <h3><i class="ri-calendar-event-line"></i> Select Date</h3>
              <div class="date-selector" id="date-selector">
                <?php foreach ($days as $day): ?>
                  <div class="date-item <?= ($day['date'] === $selectedDate) ? 'active' : '' ?>"
                       data-date="<?= $day['date'] ?>"
                       onclick="selectDate(this)">
                    <div class="day"><?= $day['day'] ?></div>
                    <div class="num"><?= $day['num'] ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Select Time -->
            <div class="booking-section">
              <h3><i class="ri-time-line"></i> Select Time</h3>
              <div class="time-grid" id="time-grid">
                <?php foreach ($allSlots as $slot):
                  $slotFull = $slot . ':00';
                  $isBooked = in_array($slotFull, $bookedSlots, true);
                  $isSelected = (($_POST['appt_time'] ?? '') === $slot);
                ?>
                  <div class="time-slot <?= $isBooked ? 'disabled' : '' ?> <?= $isSelected ? 'active' : '' ?>"
                       <?= $isBooked ? '' : 'onclick="selectTime(this)"' ?>
                       data-time="<?= $slot ?>">
                    <?= $slot ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Consultation Type -->
            <div class="booking-section">
              <h3><i class="ri-customer-service-2-line"></i> Consultation Type</h3>
              <div class="consult-types">
                <div class="consult-type <?= (($_POST['consult_type'] ?? 'in-person') === 'in-person') ? 'active' : '' ?>"
                     onclick="selectConsult(this, 'in-person')">
                  <i class="ri-hospital-line"></i>
                  <span>In-Person</span>
                </div>
                <div class="consult-type <?= (($_POST['consult_type'] ?? '') === 'video') ? 'active' : '' ?>"
                     onclick="selectConsult(this, 'video')">
                  <i class="ri-vidicon-line"></i>
                  <span>Video Call</span>
                </div>
                <div class="consult-type <?= (($_POST['consult_type'] ?? '') === 'chat') ? 'active' : '' ?>"
                     onclick="selectConsult(this, 'chat')">
                  <i class="ri-chat-voice-line"></i>
                  <span>Chat</span>
                </div>
              </div>
            </div>

            <!-- Reason -->
            <div class="booking-section">
              <h3><i class="ri-file-edit-line"></i> Reason for Visit</h3>
              <textarea name="reason" class="form-control"
                        placeholder="Briefly describe your symptoms or reason..."><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
            </div>

            <!-- Summary -->
            <div class="booking-summary">
              <div class="summary-row">
                <span>Consultation Fee</span>
                <span>$<?= number_format($fee, 2) ?></span>
              </div>
              <div class="summary-row">
                <span>Service Charge</span>
                <span>$<?= number_format($serviceCharge, 2) ?></span>
              </div>
              <div class="summary-row">
                <span>Discount</span>
                <span style="color: var(--success);">-$<?= number_format($discount, 2) ?></span>
              </div>
              <div class="summary-row total">
                <span>Total</span>
                <span>$<?= number_format($total, 2) ?></span>
              </div>
            </div>

            <!-- Confirm Button -->
            <div class="booking-btn">
              <button type="submit" name="confirm" value="1" class="btn btn-primary" id="confirm-btn">
                Confirm Booking
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </main>

  <!-- Bottom Navigation -->
  <nav class="bottom-nav">
    <a href="dashboard.php"><i class="ri-home-5-line"></i> Home</a>
    <a href="doctors.php"><i class="ri-nurse-line"></i> Doctors</a>
    <a href="booking.php" class="active"><i class="ri-calendar-check-line"></i> Book</a>
    <a href="records.php"><i class="ri-folder-open-line"></i> Records</a>
    <a href="profile.php"><i class="ri-user-3-line"></i> Profile</a>
  </nav>
</div>

<script>
function selectDate(el) {
  document.querySelectorAll('.date-item').forEach(d => d.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('hidden-date').value = el.dataset.date;
  // Reload to recalculate booked slots for new date
  const url = new URL(window.location.href);
  url.searchParams.set('date', el.dataset.date);
  window.location.href = url.toString();
}

function selectTime(el) {
  if (el.classList.contains('disabled')) return;
  document.querySelectorAll('.time-slot').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('hidden-time').value = el.dataset.time;
  document.getElementById('confirm-btn').style.opacity = '1';
}

function selectConsult(el, type) {
  document.querySelectorAll('.consult-type').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('hidden-consult').value = type;
}

// Disable confirm if no time selected
(function() {
  const btn = document.getElementById('confirm-btn');
  const time = document.getElementById('hidden-time').value;
  if (!time) btn.style.opacity = '0.5';
  btn.addEventListener('click', function(e) {
    const t = document.getElementById('hidden-time').value;
    if (!t) {
      e.preventDefault();
      alert('Please select a time slot before confirming.');
    }
  });
})();
</script>

</body>
</html>
