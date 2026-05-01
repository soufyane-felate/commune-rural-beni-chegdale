<?php
require_once 'config/db.php';

$query = "CREATE TABLE IF NOT EXISTS `complaint_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_note_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($query) === TRUE) {
    echo "Table complaint_notes created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
