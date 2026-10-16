-- ============================================================
--  MediCare+ Database
--  Import this file via phpMyAdmin or run:
--    mysql -u root -p < medicare_db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS medicare_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE medicare_db;

-- ============================================================
--  TABLE: users  (patients AND doctors, role column separates)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname      VARCHAR(120)  NOT NULL,
  email         VARCHAR(180)  NOT NULL UNIQUE,
  phone         VARCHAR(20)   DEFAULT NULL,
  dob           DATE          DEFAULT NULL,
  gender        ENUM('male','female','other') DEFAULT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: doctors
-- ============================================================
CREATE TABLE IF NOT EXISTS doctors (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED DEFAULT NULL COMMENT 'Links to users table if doctor has login',
  fullname       VARCHAR(120) NOT NULL,
  specialty      VARCHAR(100) NOT NULL,
  hospital       VARCHAR(150) DEFAULT NULL,
  distance_km    DECIMAL(5,1) DEFAULT NULL,
  rating         DECIMAL(3,1) DEFAULT 5.0,
  reviews_count  INT UNSIGNED DEFAULT 0,
  status         ENUM('online','offline','busy') DEFAULT 'offline',
  bio            TEXT         DEFAULT NULL,
  consult_fee    DECIMAL(8,2) DEFAULT 50.00,
  icon_class     VARCHAR(60)  DEFAULT 'ri-user-smile-line',
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: appointments
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  doctor_id     INT UNSIGNED NOT NULL,
  appt_date     DATE         NOT NULL,
  appt_time     TIME         NOT NULL,
  consult_type  ENUM('in-person','video','chat') DEFAULT 'in-person',
  reason        TEXT         DEFAULT NULL,
  fee           DECIMAL(8,2) DEFAULT 50.00,
  status        ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: prescriptions
-- ============================================================
CREATE TABLE IF NOT EXISTS prescriptions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  doctor_id   INT UNSIGNED NOT NULL,
  medication  VARCHAR(200) NOT NULL,
  dosage      VARCHAR(100) DEFAULT NULL,
  frequency   VARCHAR(100) DEFAULT NULL,
  start_date  DATE         DEFAULT NULL,
  end_date    DATE         DEFAULT NULL,
  status      ENUM('active','completed','expired') DEFAULT 'active',
  notes       TEXT         DEFAULT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: health_records
-- ============================================================
CREATE TABLE IF NOT EXISTS health_records (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  record_type  ENUM('lab','prescription','consultation','xray','vaccination','other') DEFAULT 'other',
  title        VARCHAR(200) NOT NULL,
  description  TEXT         DEFAULT NULL,
  icon_class   VARCHAR(60)  DEFAULT 'ri-file-text-line',
  icon_color   VARCHAR(20)  DEFAULT 'qa-blue',
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  message     TEXT         NOT NULL,
  type        ENUM('appointment','medication','prescription','payment','lab','general') DEFAULT 'general',
  icon_class  VARCHAR(60)  DEFAULT 'ri-notification-3-line',
  icon_color  VARCHAR(20)  DEFAULT 'qa-blue',
  is_read     TINYINT(1)   DEFAULT 0,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SEED DATA
-- ============================================================

-- Admin account  (password: Admin@1234)
INSERT INTO users (fullname, email, phone, password_hash, role) VALUES
('Admin', 'admin@medicare.com', NULL,
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Doctors (password for each: Doctor@1234)
INSERT INTO users (fullname, email, phone, password_hash, role) VALUES
('Dr. James Wilson',   'james.wilson@medicare.com',   '+1-555-0101', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Dr. Sarah Kim',      'sarah.kim@medicare.com',      '+1-555-0102', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Dr. Michael Roberts','michael.roberts@medicare.com','+1-555-0103', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Dr. Emily Chen',     'emily.chen@medicare.com',     '+1-555-0104', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Dr. David Lee',      'david.lee@medicare.com',      '+1-555-0105', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Dr. Amara Nkosi',    'amara.nkosi@medicare.com',    '+1-555-0106', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Dr. Priya Sharma',   'priya.sharma@medicare.com',   '+1-555-0107', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor');

-- Doctors profile rows (link user_id 2-8)
INSERT INTO doctors (user_id, fullname, specialty, hospital, distance_km, rating, reviews_count, status, bio, consult_fee, icon_class) VALUES
(2, 'Dr. James Wilson',    'General Practitioner', 'City Medical Center',      2.5, 4.9, 120, 'online',  'Dr. Wilson is a board-certified GP with 15 years of experience in primary care.',       50.00, 'ri-user-smile-line'),
(3, 'Dr. Sarah Kim',       'Cardiologist',         'Heart & Vascular Institute',3.1, 4.8,  98, 'online',  'Dr. Kim specialises in preventive cardiology and heart disease management.',            80.00, 'ri-heart-pulse-line'),
(4, 'Dr. Michael Roberts', 'Dentist',              'SmileCare Dental Clinic',  1.8, 4.7,  75, 'offline', 'Dr. Roberts offers comprehensive dental services including cosmetic dentistry.',         60.00, 'ri-user-heart-line'),
(5, 'Dr. Emily Chen',      'Pediatrician',         'Children\'s Health Center', 4.2, 4.9, 112, 'online',  'Dr. Chen has dedicated her career to the health and wellbeing of infants and children.', 65.00, 'ri-user-smile-line'),
(6, 'Dr. David Lee',       'Neurologist',          'Neuro Wellness Clinic',    5.0, 4.6,  63, 'online',  'Dr. Lee is an expert in diagnosing and treating disorders of the nervous system.',       90.00, 'ri-brain-line'),
(7, 'Dr. Amara Nkosi',     'Dermatologist',        'SkinHealth Clinic',        3.7, 4.8,  87, 'online',  'Dr. Nkosi is an award-winning dermatologist focused on skin health and aesthetics.',    75.00, 'ri-microscope-line'),
(8, 'Dr. Priya Sharma',    'Orthopedic Surgeon',   'BoneAndJoint Hospital',    6.2, 4.7,  54, 'offline', 'Dr. Sharma performs minimally invasive joint surgeries and sports medicine therapy.',   100.00, 'ri-run-line');

-- Demo patient account (password: Patient@1234)
INSERT INTO users (fullname, email, phone, dob, gender, password_hash, role) VALUES
('Antonny Demo', 'antonny@medicare.com', '+1-555-9999', '1998-05-14', 'male',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient');

-- Sample appointments for demo patient (user_id = 9)
INSERT INTO appointments (user_id, doctor_id, appt_date, appt_time, consult_type, reason, fee, status) VALUES
(9, 1, '2026-09-25', '09:30:00', 'in-person', 'Regular checkup and blood pressure monitoring.', 50.00, 'confirmed'),
(9, 2, '2026-09-28', '11:00:00', 'video',     'Follow-up on recent ECG results.',                80.00, 'pending'),
(9, 4, '2026-08-20', '14:00:00', 'in-person', 'Child annual checkup.',                           65.00, 'completed'),
(9, 1, '2026-07-10', '08:30:00', 'chat',      'General questions about headaches.',              50.00, 'completed');

-- Sample prescriptions for demo patient
INSERT INTO prescriptions (user_id, doctor_id, medication, dosage, frequency, start_date, end_date, status, notes) VALUES
(9, 1, 'Ibuprofen 400mg',    '400mg',   '3 times daily after meals',       '2026-09-07', '2026-09-21', 'active',    'Take with food. Avoid on empty stomach.'),
(9, 1, 'Paracetamol 500mg',  '500mg',   'When needed, max 4 per day',      '2026-09-07', '2026-09-21', 'active',    NULL),
(9, 1, 'Nasal Spray',        '2 sprays','Each nostril, twice daily',       '2026-09-07', '2026-09-21', 'active',    NULL),
(9, 2, 'Amoxicillin 250mg',  '250mg',   '3 times daily',                   '2026-07-10', '2026-07-20', 'completed', 'Complete the full course.'),
(9, 2, 'Cough Syrup',        '10ml',    '3 times daily',                   '2026-07-10', '2026-07-20', 'completed', NULL),
(9, 4, 'Vitamin D3 1000 IU', '1000 IU', '1 tablet daily after breakfast',  '2026-05-01', '2026-06-01', 'expired',   NULL),
(9, 4, 'Calcium 500mg',      '500mg',   '1 tablet daily with dinner',      '2026-05-01', '2026-06-01', 'expired',   NULL);

-- Sample health records for demo patient
INSERT INTO health_records (user_id, record_type, title, description, icon_class, icon_color, created_at) VALUES
(9, 'lab',          'Blood Test Results',    'Complete Blood Count (CBC)',                'ri-microscope-line', 'qa-blue',   '2026-09-05 08:00:00'),
(9, 'prescription', 'Prescription — Dr. Wilson', 'Headache treatment plan',              'ri-medicine-bottle-line','qa-green','2026-09-07 09:30:00'),
(9, 'consultation', 'Consultation Summary',  'General checkup notes',                    'ri-file-text-line',  'qa-purple', '2026-08-28 10:00:00'),
(9, 'xray',         'X-Ray Report',          'Chest X-Ray scan',                         'ri-body-scan-line',  'qa-orange', '2026-08-15 11:00:00'),
(9, 'lab',          'Urine Analysis',        'Routine urine test',                       'ri-flask-line',      'qa-teal',   '2026-08-10 07:30:00'),
(9, 'lab',          'ECG Report',            'Electrocardiogram scan',                   'ri-heart-pulse-line','qa-pink',   '2026-07-22 09:00:00'),
(9, 'vaccination',  'Vaccination Record',    'COVID-19 Booster',                         'ri-syringe-line',    'qa-blue',   '2026-07-10 14:00:00');

-- Sample notifications for demo patient
INSERT INTO notifications (user_id, title, message, type, icon_class, icon_color, is_read, created_at) VALUES
(9, 'Appointment Reminder',       'Your appointment with Dr. James Wilson is tomorrow at 9:30 AM.',                         'appointment',  'ri-calendar-event-line', 'qa-blue',   0, NOW()),
(9, 'Medication Reminder',        'Time to take your Ibuprofen 400mg — after lunch dose.',                                  'medication',   'ri-capsule-line',         'qa-green',  0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(9, 'New Prescription Available', 'Dr. Wilson has sent you a new digital prescription. View it in your records.',           'prescription', 'ri-file-list-3-line',     'qa-purple', 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(9, 'Rate Your Visit',            'How was your consultation with Dr. Sarah Kim? Share your experience.',                   'general',      'ri-star-line',            'qa-orange', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(9, 'Lab Results Ready',          'Your blood test results from Sept 5 are now available in your records.',                 'lab',          'ri-microscope-line',      'qa-teal',   1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(9, 'Payment Successful',         'Your payment of $50.00 for the consultation with Dr. Wilson was successful.',            'payment',      'ri-bank-card-line',       'qa-pink',   1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(9, 'Health Tip of the Week',     'Regular exercise for 30 minutes daily can reduce the risk of heart disease by 35%.',    'general',      'ri-hospital-line',        'qa-yellow', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 'Appointment Completed',      'Your appointment with Dr. Emily Chen on Aug 20 has been completed.',                    'appointment',  'ri-checkbox-circle-line', 'qa-blue',   1, DATE_SUB(NOW(), INTERVAL 7 DAY));
