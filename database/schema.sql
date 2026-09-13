-- ============================================================
-- Skill-Sharing Management System - Database Schema (MySQL)
-- CSC 3215 Web Technologies - PHP & MySQL implementation
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- USERS & ROLES ----------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','employer','admin') NOT NULL,
  phone VARCHAR(30),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  verification_status ENUM('unverified','pending','approved','rejected') NOT NULL DEFAULT 'unverified',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  bio TEXT,
  headline VARCHAR(200),
  location VARCHAR(150),
  avatar_url VARCHAR(255),
  education VARCHAR(255),
  experience VARCHAR(255),
  interests VARCHAR(255),
  company_name VARCHAR(150),
  website VARCHAR(255),
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- SKILLS / CATEGORIES ----------
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  category_id INT,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  skill_id INT NOT NULL,
  proficiency ENUM('beginner','intermediate','advanced','expert') NOT NULL DEFAULT 'intermediate',
  UNIQUE KEY uq_user_skill (user_id, skill_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- SKILL LISTINGS (marketplace) ----------
CREATE TABLE IF NOT EXISTS skill_listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  skill_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  level ENUM('beginner','intermediate','advanced','expert') NOT NULL DEFAULT 'beginner',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  session_duration_minutes INT NOT NULL DEFAULT 60,
  allows_paid TINYINT(1) NOT NULL DEFAULT 1,
  allows_barter TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive','removed') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- AVAILABILITY ----------
CREATE TABLE IF NOT EXISTS teacher_availability (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  listing_id INT,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  is_booked TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES skill_listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- BOOKINGS / SESSIONS ----------
CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  teacher_id INT NOT NULL,
  listing_id INT NOT NULL,
  availability_id INT,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  payment_type ENUM('paid','barter') NOT NULL DEFAULT 'paid',
  status ENUM('pending','confirmed','completed','cancelled','rescheduled') NOT NULL DEFAULT 'pending',
  meeting_id VARCHAR(64),
  meeting_url VARCHAR(255),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES skill_listings(id) ON DELETE CASCADE,
  FOREIGN KEY (availability_id) REFERENCES teacher_availability(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- LEARNING MATERIALS ----------
CREATE TABLE IF NOT EXISTS learning_materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  teacher_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  file_url VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- WISHLIST ----------
CREATE TABLE IF NOT EXISTS wishlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  listing_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wishlist (student_id, listing_id),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES skill_listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- PAYMENTS / TRANSACTIONS ----------
CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT,
  payer_id INT NOT NULL,
  payee_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  type ENUM('session_payment','refund','hiring_fee') NOT NULL DEFAULT 'session_payment',
  status ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'success',
  reference VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
  FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (payee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- BARTER ----------
CREATE TABLE IF NOT EXISTS barter_exchanges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  offered_skill_id INT NOT NULL,
  requested_skill_id INT NOT NULL,
  listing_id INT,
  booking_id INT,
  status ENUM('pending','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  message TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (offered_skill_id) REFERENCES skills(id) ON DELETE CASCADE,
  FOREIGN KEY (requested_skill_id) REFERENCES skills(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES skill_listings(id) ON DELETE SET NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- RATINGS / REVIEWS ----------
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT,
  hiring_id INT,
  reviewer_id INT NOT NULL,
  reviewee_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_booking (booking_id, reviewer_id),
  CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- VERIFICATION ----------
CREATE TABLE IF NOT EXISTS verification_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_info TEXT,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_notes TEXT,
  reviewed_by INT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- EMPLOYER / HIRING ----------
CREATE TABLE IF NOT EXISTS hiring_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employer_id INT NOT NULL,
  candidate_id INT NOT NULL,
  position_title VARCHAR(200) NOT NULL,
  message TEXT,
  status ENUM('contacted','interviewing','hired','declined','completed') NOT NULL DEFAULT 'contacted',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- REPORTS / MODERATION ----------
CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  target_type ENUM('user','listing','review') NOT NULL,
  target_id INT NOT NULL,
  reason VARCHAR(500) NOT NULL,
  status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS moderation_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id INT NOT NULL,
  admin_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- NOTIFICATIONS ----------
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  message VARCHAR(500) NOT NULL,
  link VARCHAR(255),
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- INDEXES ----------
CREATE INDEX idx_listings_teacher ON skill_listings(teacher_id);
CREATE INDEX idx_listings_skill ON skill_listings(skill_id);
CREATE INDEX idx_listings_status ON skill_listings(status);
CREATE INDEX idx_availability_teacher ON teacher_availability(teacher_id);
CREATE INDEX idx_bookings_student ON bookings(student_id);
CREATE INDEX idx_bookings_teacher ON bookings(teacher_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_transactions_payer ON transactions(payer_id);
CREATE INDEX idx_transactions_payee ON transactions(payee_id);
CREATE INDEX idx_reviews_reviewee ON reviews(reviewee_id);
CREATE INDEX idx_barter_receiver ON barter_exchanges(receiver_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_users_role ON users(role);

SET FOREIGN_KEY_CHECKS = 1;
