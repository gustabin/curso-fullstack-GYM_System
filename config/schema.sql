-- Tablas base (sin dependencias externas)
CREATE TABLE `users` (
  `user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` ENUM('admin','trainer','reception') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reception',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `members` (
  `member_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `dni` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `memberships` (
  `membership_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_days` INT(11) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `benefits` TEXT COLLATE utf8mb4_unicode_ci,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`membership_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trainers` (
  `trainer_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas con dependencias (deben ir después)
CREATE TABLE `password_resets` (
  `token` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `member_memberships` (
  `mm_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT(10) UNSIGNED NOT NULL,
  `membership_id` INT(10) UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('active','expired','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mm_id`),
  KEY `member_id` (`member_id`),
  KEY `membership_id` (`membership_id`),
  CONSTRAINT `member_memberships_ibfk_1` 
    FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `member_memberships_ibfk_2` 
    FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`membership_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendances` (
  `attendance_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT(10) UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `checked_in_at` TIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_attendance_per_day` (`member_id`, `date`),
  CONSTRAINT `attendances_ibfk_1` 
    FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `payment_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mm_id` INT(10) UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` TEXT COLLATE utf8mb4_unicode_ci,
  `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `mm_id` (`mm_id`),
  CONSTRAINT `payments_ibfk_1` 
    FOREIGN KEY (`mm_id`) REFERENCES `member_memberships` (`mm_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `logs` (
  `log_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` TEXT COLLATE utf8mb4_unicode_ci,
  `ip_address` VARCHAR(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `logs_ibfk_1` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`) VALUES
('admin', 'gustabin@yahoo.com', '$2y$10$8a2KIIbptx8ahWgV3UTma.g0dgumYLz.F6QDRXA45aMgZxTYKf5CO', 'admin', 1),
('recep', 'recep@GYM.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception', 1);

INSERT INTO `memberships` (`name`, `duration_days`, `price`, `benefits`) VALUES
('Mensual Básico', 30, 50.00, 'Acceso a máquinas básicas'),
('Trimestral Premium', 90, 120.00, 'Acceso total + 1 sesión personalizada/mes');

INSERT INTO `members` (`first_name`, `last_name`, `dni`, `phone`, `email`, `photo`) VALUES
('Juan', 'Pérez', '12345678', '987654321', 'juan@example.com', 'juan.jpg'),
('María', 'Gómez', '87654321', '912345678', 'maria@example.com', 'maria.jpg');