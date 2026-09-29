-- مدد | MADD — قاعدة بيانات MySQL
-- نفّذ الملف من phpMyAdmin أو:
-- mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS madd_wifi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE madd_wifi;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer', 'staff', 'admin') NOT NULL DEFAULT 'customer',
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_phone (phone),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_auth_tokens_token (token),
  KEY idx_auth_tokens_user (user_id),
  KEY idx_auth_tokens_expires (expires_at),
  CONSTRAINT fk_auth_tokens_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جاهز لدمج نظام الطلبات لاحقاً
CREATE TABLE IF NOT EXISTS fiber_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  subscriber_type ENUM('new', 'existing') NOT NULL DEFAULT 'new',
  plan_id VARCHAR(64) DEFAULT NULL,
  plan_speed INT UNSIGNED DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  account_number VARCHAR(64) DEFAULT NULL,
  location TEXT NOT NULL,
  status ENUM('new', 'processing', 'done', 'cancelled') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_fiber_orders_user (user_id),
  KEY idx_fiber_orders_status (status),
  CONSTRAINT fk_fiber_orders_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- مستخدم تجريبي: demo@madd.ps / password123
INSERT INTO users (name, phone, email, password_hash, role, status)
VALUES (
  'مستخدم تجريبي',
  '0590000000',
  'demo@madd.ps',
  '$2y$12$BLDmiMgSXY/TB5CEXibF5OfEHhk.mDPD.Rvj4iQxmA9l3HaQEFE1S',
  'customer',
  'active'
)
ON DUPLICATE KEY UPDATE email = email;
