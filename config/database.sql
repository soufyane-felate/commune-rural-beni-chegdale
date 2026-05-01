-- ============================================================
--   MUNICIPAL MANAGEMENT SYSTEM — DATABASE SCHEMA
--   Database : municipal_system
-- ============================================================

CREATE DATABASE IF NOT EXISTS `municipal_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `municipal_system`;

-- ============================================================
-- 1. DEPARTMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default departments
INSERT INTO `departments` (`name`) VALUES 
('Etat Civil'), 
('Legalisation'), 
('Ressources Humaines'), 
('Fiscalité');

-- ============================================================
-- 2. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(180) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','employee','citizen') NOT NULL DEFAULT 'citizen',
  `department_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_role` (`role`),
  INDEX `idx_email` (`email`),
  CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Admin (Password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@gmail.com', '$2y$10$EoUaoDxrowIaKQHROVXkh.4kBe7QzLDMXJO5PJkmdGvMJHv0aZtJ2', 'admin');

-- Default Employee (Password: Employee123)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `department_id`) VALUES
('Ahmed Employee', 'employee@benichegdale.dz', '$2y$10$wE9A6zE.Qj7gH12V3.4G2O9.Uo9A3Q1xL.mH6cQ9e.aE/vEwYwX.8', 'employee', 1);

-- Default Citizen (Password: Citizen123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Youssef Citizen', 'citizen@benichegdale.dz', '$2y$10$T8Z.X/vEwYwX.8n.oH5Z0aH3kQcE300hG0xQ3qG5mK9j6sCEwJ6E2', 'citizen');


-- ============================================================
-- 3. COMPLAINTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('pending','in_progress','resolved') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_complaint_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaint_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. COMPLAINT NOTES
-- ============================================================
CREATE TABLE IF NOT EXISTS `complaint_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_note_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ACTIVITY LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. CIVIL STATUS REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `civil_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `service_type` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `act_number` VARCHAR(50),
  `act_year` VARCHAR(4),
  `act_commune` VARCHAR(100),
  `cin_file` VARCHAR(255) NOT NULL,
  `documents` VARCHAR(255),
  `status` ENUM('pending', 'approved', 'rejected', 'need_presence') NOT NULL DEFAULT 'pending',
  `response_file` VARCHAR(255),
  `response_message` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_civil_req_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
