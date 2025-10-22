-- Create departments table with fixed list
CREATE TABLE IF NOT EXISTS `departments` (
  `department_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert fixed departments
INSERT INTO `departments` (`name`, `description`) VALUES
('Financiën', 'Financiële administratie en boekhouding'),
('Service', 'Klantenservice en support'),
('Directie', 'Management en directie'),
('Magazijn', 'Magazijn en voorraad beheer'),
('Transport', 'Transport en logistiek'),
('Planning', 'Planning en coördinatie'),
('ICT', 'ICT afdeling en technische support'),
('Externe partij', 'Externe medewerkers en partners');

-- Add department_id column to users table
ALTER TABLE `users` 
ADD COLUMN `department_id` INT(11) NULL AFTER `role`,
ADD KEY `idx_department_id` (`department_id`),
ADD CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;
