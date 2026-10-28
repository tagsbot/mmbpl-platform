-- MMBPL Digital Platform schema (MySQL 5.7+/MariaDB 10.3+, utf8mb4)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(100) NOT NULL PRIMARY KEY,
  v MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','librarian','editor') NOT NULL DEFAULT 'editor',
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biblios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  koha_biblionumber INT NULL UNIQUE,
  title VARCHAR(500) NOT NULL,
  subtitle VARCHAR(500) NULL,
  author VARCHAR(300) NULL,
  isbn VARCHAR(60) NULL,
  publisher VARCHAR(300) NULL,
  pub_year VARCHAR(20) NULL,
  edition VARCHAR(100) NULL,
  pages VARCHAR(50) NULL,
  language VARCHAR(20) NOT NULL DEFAULT 'en',
  format VARCHAR(40) NOT NULL DEFAULT 'book',
  subjects VARCHAR(1000) NULL,
  summary TEXT NULL,
  call_number VARCHAR(100) NULL,
  cover_url VARCHAR(500) NULL,
  date_added DATE NULL,
  popularity INT NOT NULL DEFAULT 0,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_lang (language),
  KEY idx_added (date_added),
  KEY idx_pop (popularity),
  FULLTEXT KEY ft_search (title, subtitle, author, subjects, isbn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  koha_itemnumber INT NULL UNIQUE,
  biblio_id INT UNSIGNED NOT NULL,
  barcode VARCHAR(60) NULL,
  shelf_location VARCHAR(150) NULL,
  call_number VARCHAR(100) NULL,
  status ENUM('available','checked_out','on_hold','in_transit','lost','damaged','reference','withdrawn') NOT NULL DEFAULT 'available',
  due_date DATE NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_biblio (biblio_id),
  KEY idx_barcode (barcode),
  CONSTRAINT fk_items_biblio FOREIGN KEY (biblio_id) REFERENCES biblios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  koha_borrowernumber INT NULL UNIQUE,
  cardnumber VARCHAR(60) NOT NULL UNIQUE,
  firstname VARCHAR(150) NULL,
  surname VARCHAR(150) NULL,
  category VARCHAR(60) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(40) NULL,
  dob DATE NULL,
  expiry_date DATE NULL,
  status ENUM('active','expired','blocked') NOT NULL DEFAULT 'active',
  fines_due DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_loans INT NOT NULL DEFAULT 5,
  photo VARCHAR(255) NULL,
  password_hash VARCHAR(255) NULL,
  activated_at DATETIME NULL,
  lang VARCHAR(5) NOT NULL DEFAULT 'en',
  notify_due TINYINT(1) NOT NULL DEFAULT 1,
  notify_holds TINYINT(1) NOT NULL DEFAULT 1,
  notify_events TINYINT(1) NOT NULL DEFAULT 1,
  notify_sms TINYINT(1) NOT NULL DEFAULT 1,
  notify_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
  notify_push TINYINT(1) NOT NULL DEFAULT 1,
  push_token VARCHAR(255) NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  koha_issue_id INT NULL UNIQUE,
  member_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  issued_at DATE NULL,
  due_date DATE NULL,
  renewals INT NOT NULL DEFAULT 0,
  renewable TINYINT(1) NOT NULL DEFAULT 1,
  not_renewable_reason VARCHAR(120) NULL,
  KEY idx_member (member_id),
  CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_loans_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS holds (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  koha_hold_id INT NULL UNIQUE,
  member_id INT UNSIGNED NOT NULL,
  biblio_id INT UNSIGNED NOT NULL,
  status ENUM('requested','waiting','ready','fulfilled','cancelled','failed') NOT NULL DEFAULT 'requested',
  queue_position INT NULL,
  pickup_by DATE NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_member (member_id),
  CONSTRAINT fk_holds_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_holds_biblio FOREIGN KEY (biblio_id) REFERENCES biblios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reading_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  biblio_id INT UNSIGNED NOT NULL,
  issued_at DATE NULL,
  returned_at DATE NULL,
  UNIQUE KEY uq_hist (member_id, biblio_id, issued_at),
  CONSTRAINT fk_hist_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_hist_biblio FOREIGN KEY (biblio_id) REFERENCES biblios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlist (
  member_id INT UNSIGNED NOT NULL,
  biblio_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id, biblio_id),
  CONSTRAINT fk_wish_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_wish_biblio FOREIGN KEY (biblio_id) REFERENCES biblios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Requests from website/app that the Sync Agent executes in Koha
CREATE TABLE IF NOT EXISTS actions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('renew','reserve','cancel_hold','create_member','update_member') NOT NULL,
  member_id INT UNSIGNED NULL,
  ref_id INT UNSIGNED NULL,
  payload TEXT NULL,
  status ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
  result VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  device VARCHAR(120) NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tok_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  purpose VARCHAR(30) NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NULL,
  type VARCHAR(30) NOT NULL DEFAULT 'library',
  title VARCHAR(255) NOT NULL,
  body TEXT NULL,
  link VARCHAR(255) NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_reads (
  notification_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (notification_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('news','event') NOT NULL DEFAULT 'news',
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  title_ur VARCHAR(255) NULL,
  excerpt VARCHAR(500) NULL,
  excerpt_ur VARCHAR(500) NULL,
  body MEDIUMTEXT NULL,
  body_ur MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  category VARCHAR(60) NULL,
  event_start DATETIME NULL,
  event_end DATETIME NULL,
  venue VARCHAR(255) NULL,
  seats INT NULL,
  registration_open TINYINT(1) NOT NULL DEFAULT 1,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_type (type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NULL,
  reg_no VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  seats INT NOT NULL DEFAULT 1,
  attended TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reg_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  slug VARCHAR(100) NOT NULL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  title_ur VARCHAR(255) NULL,
  body MEDIUMTEXT NULL,
  body_ur MEDIUMTEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_no VARCHAR(30) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  guardian_name VARCHAR(150) NULL,
  cnic VARCHAR(30) NULL,
  dob DATE NULL,
  gender VARCHAR(20) NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(500) NULL,
  city VARCHAR(100) NULL,
  membership_type VARCHAR(40) NOT NULL,
  photo VARCHAR(255) NULL,
  document VARCHAR(255) NULL,
  status ENUM('pending','approved','correction','rejected') NOT NULL DEFAULT 'pending',
  staff_note VARCHAR(500) NULL,
  member_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suggestions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NULL,
  title VARCHAR(300) NOT NULL,
  author VARCHAR(200) NULL,
  isbn VARCHAR(40) NULL,
  language VARCHAR(20) NULL,
  reason VARCHAR(500) NULL,
  name VARCHAR(150) NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  status ENUM('review','approved','purchased','rejected') NOT NULL DEFAULT 'review',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  book_type VARCHAR(200) NULL,
  quantity VARCHAR(50) NULL,
  dropoff_date DATE NULL,
  note VARCHAR(500) NULL,
  status ENUM('new','contacted','received','declined') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_no VARCHAR(30) NOT NULL UNIQUE,
  type VARCHAR(30) NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  message TEXT NOT NULL,
  attachment VARCHAR(255) NULL,
  status ENUM('new','in_progress','resolved') NOT NULL DEFAULT 'new',
  reply TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS archive_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  title_ur VARCHAR(300) NULL,
  type ENUM('photograph','newspaper','rare_book','document') NOT NULL DEFAULT 'photograph',
  year VARCHAR(20) NULL,
  place VARCHAR(150) NULL,
  source VARCHAR(255) NULL,
  rights VARCHAR(255) NULL,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  digitised TINYINT(1) NOT NULL DEFAULT 1,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam VARCHAR(30) NOT NULL,
  kind ENUM('guide','job','link') NOT NULL DEFAULT 'guide',
  title VARCHAR(300) NOT NULL,
  body TEXT NULL,
  url VARCHAR(500) NULL,
  deadline DATE NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image VARCHAR(255) NOT NULL,
  caption VARCHAR(255) NULL,
  sort INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sync_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(40) NOT NULL,
  records INT NOT NULL DEFAULT 0,
  message VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  detail VARCHAR(500) NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  k VARCHAR(120) NOT NULL PRIMARY KEY,
  hits INT NOT NULL DEFAULT 0,
  window_start DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS act_tickets (
  ticket_hash CHAR(64) NOT NULL PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
