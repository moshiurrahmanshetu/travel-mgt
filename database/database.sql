-- ==========================================================
-- Tour & Travel Booking Management System
-- Master Installation Database Schema & Default System Data
-- File: database/database.sql
-- ==========================================================

-- Disable foreign key checks during schema creation
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. Table: roles
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `tour_package_itineraries`;
DROP TABLE IF EXISTS `tour_package_images`;
DROP TABLE IF EXISTS `tour_packages`;
DROP TABLE IF EXISTS `tour_destinations`;
DROP TABLE IF EXISTS `tour_categories`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table: permissions
-- ----------------------------------------------------------
CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table: role_permissions
-- ----------------------------------------------------------
CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_role_permissions_role_id` (`role_id`),
  KEY `idx_role_permissions_permission_id` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Table: users
-- ----------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. Table: tour_categories
-- ----------------------------------------------------------
CREATE TABLE `tour_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tour_categories_slug` (`slug`),
  KEY `idx_tour_categories_status` (`status`),
  KEY `idx_tour_categories_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. Table: tour_destinations
-- ----------------------------------------------------------
CREATE TABLE `tour_destinations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tour_destinations_slug` (`slug`),
  KEY `idx_tour_destinations_status` (`status`),
  KEY `idx_tour_destinations_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. Table: tour_packages
-- ----------------------------------------------------------
CREATE TABLE `tour_packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `destination_id` INT UNSIGNED NOT NULL,
  `package_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(170) NOT NULL,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `duration_days` INT UNSIGNED NOT NULL DEFAULT 1,
  `duration_nights` INT UNSIGNED NOT NULL DEFAULT 0,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `child_price` DECIMAL(10,2) DEFAULT NULL,
  `discount_type` ENUM('none', 'percentage', 'fixed') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `available_seats` INT UNSIGNED NOT NULL DEFAULT 20,
  `departure_location` VARCHAR(150) DEFAULT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `hotel_information` TEXT DEFAULT NULL,
  `transportation` TEXT DEFAULT NULL,
  `meal_information` TEXT DEFAULT NULL,
  `included_services` LONGTEXT DEFAULT NULL,
  `excluded_services` LONGTEXT DEFAULT NULL,
  `terms_conditions` LONGTEXT DEFAULT NULL,
  `status` ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'draft',
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tour_packages_code` (`package_code`),
  UNIQUE KEY `uk_tour_packages_slug` (`slug`),
  KEY `idx_tour_packages_category` (`category_id`),
  KEY `idx_tour_packages_destination` (`destination_id`),
  KEY `idx_tour_packages_status` (`status`),
  KEY `idx_tour_packages_featured` (`featured`),
  KEY `idx_tour_packages_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_tour_packages_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tour_packages_destination` FOREIGN KEY (`destination_id`) REFERENCES `tour_destinations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. Table: tour_package_images
-- ----------------------------------------------------------
CREATE TABLE `tour_package_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(150) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tour_package_images_package` (`package_id`),
  KEY `idx_tour_package_images_primary` (`is_primary`),
  CONSTRAINT `fk_tour_package_images_package` FOREIGN KEY (`package_id`) REFERENCES `tour_packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. Table: tour_package_itineraries
-- ----------------------------------------------------------
CREATE TABLE `tour_package_itineraries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` INT UNSIGNED NOT NULL,
  `day_number` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tour_package_itineraries_package` (`package_id`),
  KEY `idx_tour_package_itineraries_day` (`package_id`, `day_number`),
  CONSTRAINT `fk_tour_package_itineraries_package` FOREIGN KEY (`package_id`) REFERENCES `tour_packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 10. Table: customers
-- ----------------------------------------------------------
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_code` VARCHAR(30) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `alternate_phone` VARCHAR(25) DEFAULT NULL,
  `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(50) DEFAULT NULL,
  `state` VARCHAR(50) DEFAULT NULL,
  `country` VARCHAR(50) DEFAULT 'Bangladesh',
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `passport_number` VARCHAR(50) DEFAULT NULL,
  `passport_expiry` DATE DEFAULT NULL,
  `national_id` VARCHAR(50) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_code` (`customer_code`),
  UNIQUE KEY `uk_customer_email` (`email`),
  UNIQUE KEY `uk_customer_phone` (`phone`),
  INDEX `idx_customers_name` (`name`),
  INDEX `idx_customers_status` (`status`),
  INDEX `idx_customers_created_at` (`created_at`),
  INDEX `idx_customers_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_customers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 11. Table: bookings
-- ----------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_number` VARCHAR(30) NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `tour_package_id` INT UNSIGNED NOT NULL,
  `travel_date` DATE NOT NULL,
  `num_adults` INT UNSIGNED NOT NULL DEFAULT 1,
  `num_children` INT UNSIGNED NOT NULL DEFAULT 0,
  `num_infants` INT UNSIGNED NOT NULL DEFAULT 0,
  `adult_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `child_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` ENUM('none', 'percentage', 'fixed') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `booking_status` ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'partial', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  `special_requests` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_booking_number` (`booking_number`),
  INDEX `idx_bookings_customer` (`customer_id`),
  INDEX `idx_bookings_tour` (`tour_package_id`),
  INDEX `idx_bookings_travel_date` (`travel_date`),
  INDEX `idx_bookings_status` (`booking_status`),
  INDEX `idx_bookings_payment_status` (`payment_status`),
  INDEX `idx_bookings_created_at` (`created_at`),
  INDEX `idx_bookings_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_tour` FOREIGN KEY (`tour_package_id`) REFERENCES `tour_packages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 12. Table: payments
-- ----------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_number` VARCHAR(30) NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'bank_transfer', 'credit_card', 'mobile_banking', 'other') NOT NULL DEFAULT 'cash',
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'completed',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_number` (`payment_number`),
  INDEX `idx_payments_booking` (`booking_id`),
  INDEX `idx_payments_date` (`payment_date`),
  INDEX `idx_payments_method` (`payment_method`),
  INDEX `idx_payments_status` (`payment_status`),
  INDEX `idx_payments_created_at` (`created_at`),
  INDEX `idx_payments_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 13. Table: settings
-- ----------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- DEFAULT SYSTEM SEED DATA
-- ==========================================================

-- Seed Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Administrator', 'administrator', 'Full system access and administrative control', 1),
(2, 'Manager', 'manager', 'Operational management and reporting access', 1),
(3, 'Staff', 'staff', 'Standard frontline staff access', 1);

-- Seed Permissions (1 through 45)
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`) VALUES
(1, 'View Dashboard', 'dashboard.view', 'Access to system overview and metrics'),
(2, 'View Profile', 'profile.view', 'View own account profile details'),
(3, 'Edit Profile', 'profile.edit', 'Modify own account profile details'),
(4, 'Change Password', 'password.change', 'Update own account password'),
(5, 'View Users', 'users.view', 'View list of system staff and users'),
(6, 'Create Users', 'users.create', 'Add new user accounts'),
(7, 'Edit Users', 'users.edit', 'Update existing user accounts'),
(8, 'Delete Users', 'users.delete', 'Soft-delete or deactivate user accounts'),
(9, 'View Tour Packages', 'tours.view', 'View tour package list and details'),
(10, 'Create Tour Packages', 'tours.create', 'Create new tour packages'),
(11, 'Edit Tour Packages', 'tours.edit', 'Modify existing tour packages'),
(12, 'Delete Tour Packages', 'tours.delete', 'Soft-delete tour packages'),
(13, 'View Tour Categories', 'categories.view', 'View tour category list'),
(14, 'Create Tour Categories', 'categories.create', 'Create new tour categories'),
(15, 'Edit Tour Categories', 'categories.edit', 'Modify existing tour categories'),
(16, 'Delete Tour Categories', 'categories.delete', 'Soft-delete tour categories'),
(17, 'View Tour Destinations', 'destinations.view', 'View tour destination list'),
(18, 'Create Tour Destinations', 'destinations.create', 'Create new tour destinations'),
(19, 'Edit Tour Destinations', 'destinations.edit', 'Modify existing tour destinations'),
(20, 'Delete Tour Destinations', 'destinations.delete', 'Soft-delete tour destinations'),
(21, 'View Customers', 'customers.view', 'View customer directory and profile details'),
(22, 'Create Customers', 'customers.create', 'Add and register new customers'),
(23, 'Edit Customers', 'customers.edit', 'Update existing customer profiles'),
(24, 'Delete Customers', 'customers.delete', 'Soft delete customer profiles'),
(25, 'Restore Customers', 'customers.restore', 'Restore soft-deleted customer profiles'),
(26, 'View Bookings', 'bookings.view', 'Can view booking list and reservation vouchers'),
(27, 'Create Bookings', 'bookings.create', 'Can create and process new tour bookings'),
(28, 'Edit Bookings', 'bookings.edit', 'Can modify existing tour bookings'),
(29, 'Cancel Bookings', 'bookings.cancel', 'Can cancel tour reservations'),
(30, 'Confirm Bookings', 'bookings.confirm', 'Can confirm pending tour reservations'),
(31, 'Complete Bookings', 'bookings.complete', 'Can mark confirmed bookings as completed'),
(32, 'View Payments', 'payments.view', 'Can view payment transactions and receipts'),
(33, 'Create Payments', 'payments.create', 'Can record new customer payments'),
(34, 'Edit Payments', 'payments.edit', 'Can modify payment transaction records'),
(35, 'Delete Payments', 'payments.delete', 'Can soft-delete payment transactions'),
(36, 'View Reports', 'reports.view', 'Can view management reports and business analytics dashboards'),
(37, 'Export Reports', 'reports.export', 'Can download and export report datasets to CSV spreadsheets'),
(38, 'View Roles', 'roles.view', 'Can view role list and permission mappings'),
(39, 'Create Roles', 'roles.create', 'Can create new system user roles'),
(40, 'Edit Roles', 'roles.edit', 'Can update existing roles and role permissions'),
(41, 'Delete Roles', 'roles.delete', 'Can soft-delete custom user roles'),
(42, 'View Permissions', 'permissions.view', 'Can view system permission catalog'),
(43, 'Assign Permissions', 'permissions.assign', 'Can assign permissions to roles'),
(44, 'View Settings', 'settings.view', 'Can view system configuration and settings'),
(45, 'Edit Settings', 'settings.edit', 'Can modify system configuration and company information');

-- Assign Permissions to Administrator (All permissions 1-45)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
(1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20),
(1, 21), (1, 22), (1, 23), (1, 24), (1, 25),
(1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31),
(1, 32), (1, 33), (1, 34), (1, 35),
(1, 36), (1, 37),
(1, 38), (1, 39), (1, 40), (1, 41), (1, 42), (1, 43), (1, 44), (1, 45);

-- Assign Permissions to Manager
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5),
(2, 9), (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 17), (2, 18), (2, 19),
(2, 21), (2, 22), (2, 23), (2, 24),
(2, 26), (2, 27), (2, 28), (2, 29), (2, 30), (2, 31),
(2, 32), (2, 33), (2, 34), (2, 35),
(2, 36), (2, 37),
(2, 38), (2, 42), (2, 44);

-- Assign Permissions to Staff
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 2), (3, 3), (3, 4),
(3, 9), (3, 13), (3, 17),
(3, 21), (3, 22),
(3, 26), (3, 27),
(3, 32), (3, 33),
(3, 36);

-- Seed Starter Categories
INSERT INTO `tour_categories` (`id`, `name`, `slug`, `description`, `status`) VALUES
(1, 'Domestic Tour', 'domestic-tour', 'Explore scenic destinations across Bangladesh', 'active'),
(2, 'International Tour', 'international-tour', 'Exciting holiday tours worldwide', 'active'),
(3, 'Honeymoon Special', 'honeymoon-special', 'Romantic escapes and couples luxury getaways', 'active'),
(4, 'Family Package', 'family-package', 'Comfortable, kid-friendly holidays for families', 'active'),
(5, 'Adventure & Trekking', 'adventure-trekking', 'Thrilling trails, hill tracts, and camping adventures', 'active'),
(6, 'Coastal & Beach', 'coastal-beach', 'Sun, sea, and relaxation along picturesque shorelines', 'active');

-- Seed Starter Destinations
INSERT INTO `tour_destinations` (`id`, `name`, `slug`, `country`, `description`, `image`, `status`) VALUES
(1, 'Cox\'s Bazar', 'coxs-bazar', 'Bangladesh', 'World\'s longest unbroken natural sea beach with breathtaking sunsets.', NULL, 'active'),
(2, 'Sajek Valley', 'sajek-valley', 'Bangladesh', 'The Queen of Hills known for mesmerizing cloudscapes and lush green peaks.', NULL, 'active'),
(3, 'Sreemangal', 'sreemangal', 'Bangladesh', 'The tea capital of Bangladesh surrounded by rainforests and lush green tea estates.', NULL, 'active'),
(4, 'Saint Martin\'s Island', 'saint-martins-island', 'Bangladesh', 'A pristine tropical coral island in the northeastern part of the Bay of Bengal.', NULL, 'active'),
(5, 'Sundarbans', 'sundarbans', 'Bangladesh', 'World\'s largest mangrove forest, home to the majestic Royal Bengal Tiger.', NULL, 'active'),
(6, 'Sylhet & Ratargul', 'sylhet-ratargul', 'Bangladesh', 'Picturesque freshwater swamp forest and crystal-clear rivers.', NULL, 'active');

-- Seed Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'GlobeTrek Travels & Tours'),
('company_email', 'info@globetrektravels.com'),
('company_phone', '+880 1700-000000'),
('company_address', 'Level 4, Plot 12, Gulshan-2, Dhaka-1212, Bangladesh'),
('company_website', 'https://www.globetrektravels.com'),
('currency', 'BDT'),
('currency_symbol', '৳'),
('timezone', 'Asia/Dhaka');
