<?php
require_once 'config/db.php';

$query = "CREATE TABLE IF NOT EXISTS `civil_requests` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($query) === TRUE) {
    echo "Table civil_requests created successfully\n";
    
    // Also create uploads directory
    $upload_dir = __DIR__ . '/uploads/civil_requests';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "Uploads directory created successfully\n";
    }
} else {
    echo "Error creating table: " . $conn->error;
}
?>
