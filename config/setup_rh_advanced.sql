USE municipal_system;

CREATE TABLE IF NOT EXISTS `employee_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `cin` VARCHAR(50) DEFAULT NULL,
  `matricule` VARCHAR(50) DEFAULT NULL,
  `position` VARCHAR(150) DEFAULT NULL,
  `base_salary` DECIMAL(10,2) DEFAULT 0.00,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `hiring_date` DATE DEFAULT NULL,
  `status` ENUM('active', 'suspended', 'terminated') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('cin', 'diploma', 'contract', 'other') NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_doc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add medical certificate column to leave_requests if it doesn't exist
ALTER TABLE `leave_requests` 
ADD COLUMN IF NOT EXISTS `medical_certificate` VARCHAR(255) DEFAULT NULL AFTER `reason`;
