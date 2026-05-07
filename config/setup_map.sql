USE municipal_system;

CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_fr` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `description_fr` TEXT,
  `description_ar` TEXT,
  `working_hours_fr` VARCHAR(255),
  `working_hours_ar` VARCHAR(255),
  `contact_info` VARCHAR(255),
  `image` VARCHAR(255) DEFAULT NULL,
  `service_link` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default markers based on user request (Latitude: 32.44425, Longitude: -6.96037)
INSERT INTO `locations` (`name_fr`, `name_ar`, `type`, `latitude`, `longitude`, `description_fr`, `description_ar`, `working_hours_fr`, `working_hours_ar`, `contact_info`, `service_link`) VALUES
('Siège de la Commune', 'مقر الجماعة', 'headquarters', 32.444250, -6.960370, 'Bâtiment principal de la commune rurale Beni Chegdal.', 'المبنى الرئيسي للجماعة القروية بني شكدال.', 'Lun-Ven: 08:30 - 16:30', 'الاثنين-الجمعة: 08:30 - 16:30', '0523456789', '../index.php'),
('Service État Civil', 'مصلحة الحالة المدنية', 'etat_civil', 32.444500, -6.960100, 'Service des extraits de naissance, actes de mariage, etc.', 'مصلحة عقود الازدياد، عقود الزواج، إلخ.', 'Lun-Ven: 08:30 - 16:30', 'الاثنين-الجمعة: 08:30 - 16:30', 'etat_civil@benichegdale.dz', '../citizen/civil_services.php'),
('Service Légalisation', 'مصلحة التصديق', 'legalisation', 32.444000, -6.960500, 'Légalisation de signature et certification de documents.', 'المصادقة على التوقيعات والنسخ المطابقة للأصل.', 'Lun-Ven: 08:30 - 16:30', 'الاثنين-الجمعة: 08:30 - 16:30', 'legalisation@benichegdale.dz', '../citizen/legalisation.php'),
('Service Fiscalité', 'مصلحة الضرائب', 'tax', 32.444600, -6.961000, 'Service de paiement des taxes locales.', 'مصلحة أداء الضرائب المحلية.', 'Lun-Ven: 08:30 - 16:30', 'الاثنين-الجمعة: 08:30 - 16:30', 'tax@benichegdale.dz', ''),
('Service Social', 'المصلحة الاجتماعية', 'social', 32.443800, -6.960000, 'Assistance et services sociaux pour les citoyens.', 'المساعدة والخدمات الاجتماعية للمواطنين.', 'Lun-Ven: 08:30 - 16:30', 'الاثنين-الجمعة: 08:30 - 16:30', 'social@benichegdale.dz', ''),
('École Primaire', 'المدرسة الابتدائية', 'school', 32.445500, -6.958000, 'École primaire publique de la commune.', 'المدرسة الابتدائية العمومية بالجماعة.', 'Lun-Sam: 08:00 - 12:00, 14:00 - 18:00', 'الاثنين-السبت: 08:00 - 12:00, 14:00 - 18:00', '', ''),
('Centre de Santé', 'المركز الصحي', 'health', 32.443000, -6.962000, 'Centre de santé rural, consultations et urgences.', 'المركز الصحي القروي، الفحوصات والمستعجلات.', '24/7 Urgences', 'مستعجلات 24/7', '15 (Urgences)', ''),
('Mosquée Centrale', 'المسجد المركزي', 'mosque', 32.444200, -6.959000, 'La mosquée principale de Beni Chegdal.', 'المسجد الرئيسي بجماعة بني شكدال.', 'Ouvert', 'مفتوح', '', '');
