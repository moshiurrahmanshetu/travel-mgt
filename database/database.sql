-- ==========================================================
-- Tour & Travel Booking Management System
-- Phase 01 + Phase 02: Complete Database Schema & Seed Data
-- Main Database File: database.sql
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `travel_mgt_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `travel_mgt_db`;

-- Disable foreign key checks during schema creation
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. Table: roles
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `tour_package_itineraries`;
DROP TABLE IF EXISTS `tour_package_images`;
DROP TABLE IF EXISTS `tour_packages`;
DROP TABLE IF EXISTS `tour_destinations`;
DROP TABLE IF EXISTS `tour_categories`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
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
  `country` VARCHAR(100) DEFAULT 'Bangladesh',
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
  `package_code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `duration_days` INT UNSIGNED NOT NULL DEFAULT 1,
  `duration_nights` INT UNSIGNED NOT NULL DEFAULT 0,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `child_price` DECIMAL(12,2) DEFAULT NULL,
  `discount_type` ENUM('none', 'percentage', 'fixed') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `available_seats` INT UNSIGNED NOT NULL DEFAULT 0,
  `departure_location` VARCHAR(150) DEFAULT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `hotel_information` TEXT DEFAULT NULL,
  `transportation` TEXT DEFAULT NULL,
  `meal_information` TEXT DEFAULT NULL,
  `included_services` TEXT DEFAULT NULL,
  `excluded_services` TEXT DEFAULT NULL,
  `terms_conditions` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'draft') NOT NULL DEFAULT 'active',
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
  `tour_package_id` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tour_images_package` (`tour_package_id`),
  CONSTRAINT `fk_tour_images_package` FOREIGN KEY (`tour_package_id`) REFERENCES `tour_packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. Table: tour_package_itineraries
-- ----------------------------------------------------------
CREATE TABLE `tour_package_itineraries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_package_id` INT UNSIGNED NOT NULL,
  `day_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tour_itineraries_package` (`tour_package_id`),
  CONSTRAINT `fk_tour_itineraries_package` FOREIGN KEY (`tour_package_id`) REFERENCES `tour_packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 10. Table: customers
-- ----------------------------------------------------------
CREATE TABLE `customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_code` VARCHAR(30) NOT NULL,
  `first_name` VARCHAR(100) NULL DEFAULT NULL,
  `last_name` VARCHAR(100) NULL DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `email` VARCHAR(150) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `alternate_phone` VARCHAR(30) NULL DEFAULT NULL,
  `gender` ENUM('male', 'female', 'other') NULL DEFAULT NULL,
  `date_of_birth` DATE NULL DEFAULT NULL,
  `address` TEXT NULL DEFAULT NULL,
  `city` VARCHAR(100) NULL DEFAULT NULL,
  `state` VARCHAR(100) NULL DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  `postal_code` VARCHAR(20) NULL DEFAULT NULL,
  `passport_number` VARCHAR(50) NULL DEFAULT NULL,
  `passport_expiry` DATE NULL DEFAULT NULL,
  `national_id` VARCHAR(50) NULL DEFAULT NULL,
  `profile_photo` VARCHAR(255) NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_code` (`customer_code`),
  INDEX `idx_customers_email` (`email`),
  INDEX `idx_customers_phone` (`phone`),
  INDEX `idx_customers_status` (`status`),
  INDEX `idx_customers_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 11. Table: bookings
-- ----------------------------------------------------------
CREATE TABLE `bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_number` VARCHAR(30) NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `tour_package_id` INT UNSIGNED NOT NULL,
  `travel_date` DATE NOT NULL,
  `adults` INT UNSIGNED NOT NULL DEFAULT 1,
  `children` INT UNSIGNED NOT NULL DEFAULT 0,
  `infants` INT UNSIGNED NOT NULL DEFAULT 0,
  `adult_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `child_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` ENUM('none', 'percentage', 'fixed') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `due_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `booking_status` ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'partial', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  `special_request` TEXT NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_booking_number` (`booking_number`),
  INDEX `idx_bookings_customer` (`customer_id`),
  INDEX `idx_bookings_tour_package` (`tour_package_id`),
  INDEX `idx_bookings_travel_date` (`travel_date`),
  INDEX `idx_bookings_status` (`booking_status`),
  INDEX `idx_bookings_payment_status` (`payment_status`),
  INDEX `idx_bookings_created_at` (`created_at`),
  INDEX `idx_bookings_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_tour_package` FOREIGN KEY (`tour_package_id`) REFERENCES `tour_packages` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Seed Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Administrator', 'administrator', 'Full system access and administrative control'),
(2, 'Manager', 'manager', 'Operational management and reporting access'),
(3, 'Staff', 'staff', 'Standard frontline staff access');

-- Seed Permissions (Phase 01 + Phase 02 + Phase 03)
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
(31, 'Complete Bookings', 'bookings.complete', 'Can mark confirmed bookings as completed');

-- Assign Permissions to Administrator (All permissions 1-31)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
(1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16), (1, 17), (1, 18), (1, 19), (1, 20),
(1, 21), (1, 22), (1, 23), (1, 24), (1, 25),
(1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31);

-- Assign Permissions to Manager
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5),
(2, 9), (2, 10), (2, 11), (2, 12), (2, 13), (2, 14), (2, 15), (2, 17), (2, 18), (2, 19),
(2, 21), (2, 22), (2, 23), (2, 24),
(2, 26), (2, 27), (2, 28), (2, 29), (2, 30), (2, 31);

-- Assign Permissions to Staff
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, 1), (3, 2), (3, 3), (3, 4),
(3, 9), (3, 13), (3, 17),
(3, 21), (3, 22),
(3, 26), (3, 27);

-- Seed Default Administrator
-- Default credentials: admin@example.com / Admin@12345
INSERT INTO `users` (
  `id`, `role_id`, `first_name`, `last_name`, `name`, `email`, `phone`, `password`, `avatar`, `status`, `last_login`, `created_at`, `updated_at`, `deleted_at`
) VALUES (
  1,
  1,
  'System',
  'Administrator',
  'System Administrator',
  'admin@example.com',
  '+880 1700-000000',
  '$2y$10$ph2GxsknjyDke9r1JWQzC.O0qRITP16Yous89xlsDb4vXPCEZP/Wy',
  NULL,
  'active',
  NULL,
  NOW(),
  NOW(),
  NULL
);

-- Seed Categories
INSERT INTO `tour_categories` (`id`, `name`, `slug`, `description`, `status`) VALUES
(1, 'Domestic Tour', 'domestic-tour', 'Explore scenic destinations across Bangladesh', 'active'),
(2, 'International Tour', 'international-tour', 'Exciting holiday tours worldwide', 'active'),
(3, 'Honeymoon Special', 'honeymoon-special', 'Romantic escapes and couples luxury getaways', 'active'),
(4, 'Family Package', 'family-package', 'Comfortable, kid-friendly holidays for families', 'active'),
(5, 'Adventure & Trekking', 'adventure-trekking', 'Thrilling trails, hill tracts, and camping adventures', 'active'),
(6, 'Coastal & Beach', 'coastal-beach', 'Sun, sea, and relaxation along picturesque shorelines', 'active');

-- Seed Destinations
INSERT INTO `tour_destinations` (`id`, `name`, `slug`, `country`, `description`, `image`, `status`) VALUES
(1, 'Cox\'s Bazar', 'coxs-bazar', 'Bangladesh', 'World\'s longest unbroken natural sea beach with breathtaking sunsets.', NULL, 'active'),
(2, 'Sajek Valley', 'sajek-valley', 'Bangladesh', 'The Queen of Hills known for mesmerizing cloudscapes and lush green peaks.', NULL, 'active'),
(3, 'Sreemangal', 'sreemangal', 'Bangladesh', 'The tea capital of Bangladesh surrounded by rainforests and lush green tea estates.', NULL, 'active'),
(4, 'Saint Martin\'s Island', 'saint-martins-island', 'Bangladesh', 'A pristine tropical coral island in the northeastern part of the Bay of Bengal.', NULL, 'active'),
(5, 'Sundarbans', 'sundarbans', 'Bangladesh', 'World\'s largest mangrove forest, home to the majestic Royal Bengal Tiger.', NULL, 'active'),
(6, 'Sylhet & Ratargul', 'sylhet-ratargul', 'Bangladesh', 'Picturesque freshwater swamp forest and crystal-clear rivers.', NULL, 'active');

-- Seed Sample Tour Packages
INSERT INTO `tour_packages` (
  `id`, `category_id`, `destination_id`, `package_code`, `name`, `slug`, 
  `short_description`, `description`, `duration_days`, `duration_nights`, 
  `price`, `child_price`, `discount_type`, `discount_value`, `available_seats`, 
  `departure_location`, `featured_image`, `hotel_information`, `transportation`, 
  `meal_information`, `included_services`, `excluded_services`, `terms_conditions`, 
  `status`, `featured`
) VALUES
(
  1, 1, 1, 'TP-00001', 'Cox\'s Bazar 3 Days 2 Nights Premium Beach Tour', 'coxs-bazar-3-days-2-nights-premium-beach-tour',
  'Experience the majestic Bay of Bengal with luxury beachfront accommodation, private transfers, and Inani Beach exploration.',
  'Enjoy an unforgettable 3-day holiday in Cox\'s Bazar. Relax on the world\'s longest sandy beach, visit the coral beaches of Inani and the natural springs of Himchari, and relish authentic local seafood.',
  3, 2, 12500.00, 7500.00, 'fixed', 1000.00, 24,
  'Dhaka (Sayedabad / Kalyanpur)', NULL,
  '3-Star Beach Resort (Twin/Double Sharing AC Rooms)',
  'AC Hino 1J / Hyundai Business Class Bus & Private Jeep (Chander Gari)',
  'Daily Breakfast, 2 Special Seafood Dinners, 3 Lunches',
  'AC Bus Tickets (Both ways)\nHotel Accommodation (2 Nights)\nAll Sightseeing as per Itinerary\nProfessional Tour Guide\nDaily Mineral Water',
  'Personal expenses\nEntry fees to optional rides\nAny extra meals or beverages',
  'Booking must be confirmed at least 7 days before departure.\n50% advance required for seat reservation.\nCancellation within 3 days is non-refundable.',
  'active', 1
),
(
  2, 5, 2, 'TP-00002', 'Sajek Valley 2 Days 1 Night Cloud Adventure', 'sajek-valley-2-days-1-night-cloud-adventure',
  'Touch the clouds on top of Sajek Valley with Konglak Para hiking, Helipad stargazing, and traditional indigenous cuisine.',
  'Travel to the breathtaking hills of Sajek Valley. Enjoy the thrilling open-top 4x4 Chander Gari ride through lush mountains under military escort, witness sunrise from Helipad 2, and hike up to the highest peak at Konglak Para.',
  2, 1, 8500.00, 5000.00, 'percentage', 10.00, 16,
  'Dhaka (Arambagh / Gabtoli)', NULL,
  'Eco Cottage in Sajek (Valley-view Wooden Cottage)',
  'AC Bus from Dhaka to Khagrachhari & Reserved 4x4 Chander Gari in Sajek',
  '2 Breakfasts, 2 Lunches, 1 BBQ Dinner with Bamboo Chicken',
  'Dhaka-Khagrachhari-Dhaka AC Bus\nReserved Chander Gari for full trip\n1 Night stay at Sajek Eco Resort\nTour Guide and Security Escort',
  'Medicines or personal shopping\nAlutila Cave torch fee\nAny unspecified food items',
  'National ID card copy is mandatory for check-posts.\nEco cottages have solar power schedule.\nSubject to weather and security clearance.',
  'active', 1
),
(
  3, 1, 3, 'TP-00003', 'Sreemangal Tea Garden & Wildlife Retreat', 'sreemangal-tea-garden-wildlife-retreat',
  'Immerse in endless green tea gardens, Lawachara Rainforest wildlife, and the famous Seven-Layer Tea.',
  'Escape the city hustle into the serene greenery of Sreemangal. Explore century-old tea gardens, hike inside Lawachara National Park in search of hoolock gibbons, and discover tribal villages.',
  2, 1, 6500.00, 4000.00, 'none', 0.00, 20,
  'Dhaka (Kamalapur / Mohakhali)', NULL,
  'Grand Green Resort or similar AC Deluxe Room',
  'AC Train (Parabat/Jayantika) or Non-AC/AC Bus & Local Auto/Car',
  '1 Breakfast, 2 Lunches, 1 Dinner',
  'Train/Bus transport\nHotel stay for 1 Night\nLawachara National Park entry & guide\nTea tasting session',
  'Seven Layer Tea bill\nPersonal tips\nExtra snacks',
  'Train tickets are subject to Bangladesh Railway availability.\nValid NID required for booking.',
  'active', 0
);

-- Seed Sample Itineraries
INSERT INTO `tour_package_itineraries` (`tour_package_id`, `day_number`, `title`, `description`) VALUES
(1, 1, 'Arrival in Cox\'s Bazar & Beach Leisure', 'Depart from Dhaka by overnight AC bus. Arrive in Cox\'s Bazar in the morning. Check-in to resort, relax, enjoy lunch with fresh seafood. Spend the afternoon swimming at Laboni & Kolatoli beach and witness the stunning sunset. Group dinner at beachside restaurant.'),
(1, 2, 'Himchari Waterfalls & Inani Coral Beach', 'After breakfast, take an open 4x4 Chander Gari drive along Marine Drive road. Visit Himchari hill and waterfall viewpoint. Continue to Inani Beach to walk among coral stones. Enjoy lunch at Inani. Evening free for shopping at the Burmese Market. Special BBQ dinner at night.'),
(1, 3, 'Moheshkhali Island / Sunset & Return', 'Morning breakfast. Optional speed boat ride to Moheshkhali Adinath Temple or relax at beach. Check out by 12:00 PM. Lunch and afternoon free time for photography. Board evening return AC bus to Dhaka.'),
(2, 1, 'Khagrachhari to Sajek Valley & Konglak Peak', 'Arrive at Khagrachhari early morning. Breakfast and board reserved 4x4 Chander Gari. Join military escort at Dighinala. Reach Sajek Valley by 1:00 PM. Check-in to eco-cottage. Afternoon trek to Konglak Para peak. Evening cloud watching and BBQ dinner with traditional bamboo chicken.'),
(2, 2, 'Sunrise at Helipad, Alutila Cave & Return', 'Wake up at 5:30 AM to witness the ocean of clouds from Sajek Helipad. Breakfast and check out. Depart Sajek at 10:00 AM escort. Visit Risang Waterfall and Alutila Mysterious Cave in Khagrachhari. Dinner in Khagrachhari town and board night coach to Dhaka.'),
(3, 1, 'Tea Estates & Monipuri Tribal Village', 'Morning train/bus from Dhaka to Sreemangal. Check-in to resort. Visit Finlay and BTRI Tea Gardens. Experience cycling through tea trails. Visit Monipuri tribal handloom village in the evening. Taste Nilkantha 7-layer tea.'),
(3, 2, 'Lawachara Rainforest Hike & Madhabpur Lake', 'Early morning trek inside Lawachara National Park. Spot bird species and endangered gibbons. Visit Madhabpur Lake adorned with water lilies. Afternoon lunch and depart for Dhaka.');

-- Seed Sample Customers
INSERT INTO `customers` (
  `id`, `customer_code`, `first_name`, `last_name`, `name`, `email`, `phone`, `alternate_phone`, 
  `gender`, `date_of_birth`, `address`, `city`, `state`, `country`, `postal_code`, 
  `passport_number`, `passport_expiry`, `national_id`, `profile_photo`, `notes`, `status`, `created_at`, `updated_at`
) VALUES
(
  1, 'CUS-00001', 'Tanvir', 'Ahmed', 'Tanvir Ahmed', 'tanvir.ahmed@example.com', '+8801711000001', '+8801811000001',
  'male', '1990-05-15', 'House 12, Road 5, Dhanmondi', 'Dhaka', 'Dhaka Division', 'Bangladesh', '1209',
  'A01234567', '2030-12-31', '19901234567890123', NULL, 'VIP corporate client, prefers window seat and quiet hotels.', 'active', NOW(), NOW()
),
(
  2, 'CUS-00002', 'Nusrat', 'Jahan', 'Nusrat Jahan', 'nusrat.jahan@example.com', '+8801911000002', NULL,
  'female', '1995-08-22', 'Flat 4B, Green Road', 'Dhaka', 'Dhaka Division', 'Bangladesh', '1205',
  'B09876543', '2028-06-15', '19959876543210987', NULL, 'Travels with family, interested in beach resort packages.', 'active', NOW(), NOW()
),
(
  3, 'CUS-00003', 'Mohammad', 'Rahim', 'Mohammad Rahim', 'm.rahim@example.com', '+8801811000003', NULL,
  'male', '1988-11-03', 'GEC Circle, Nasirabad', 'Chittagong', 'Chittagong Division', 'Bangladesh', '4000',
  NULL, NULL, '19881122334455667', NULL, 'Frequent domestic trekker.', 'active', NOW(), NOW()
);

-- Seed Sample Bookings (Phase 04)
INSERT INTO `bookings` (
  `id`, `booking_number`, `customer_id`, `tour_package_id`, `travel_date`,
  `adults`, `children`, `infants`, `adult_price`, `child_price`, `subtotal`,
  `discount_type`, `discount_value`, `discount_amount`, `total_amount`,
  `paid_amount`, `due_amount`, `booking_status`, `payment_status`,
  `special_request`, `notes`, `created_by`, `created_at`, `updated_at`
) VALUES
(
  1, 'BK-2026-00001', 1, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY),
  2, 1, 0, 12500.00, 7500.00, 32500.00,
  'fixed', 1000.00, 1000.00, 31500.00,
  0.00, 31500.00, 'confirmed', 'unpaid',
  'Sea view double room requested.', 'VIP client. Confirmed via phone booking.', 1, NOW(), NOW()
),
(
  2, 'BK-2026-00002', 2, 2, DATE_ADD(CURRENT_DATE(), INTERVAL 21 DAY),
  2, 0, 0, 8500.00, 5000.00, 17000.00,
  'percentage', 10.00, 1700.00, 15300.00,
  0.00, 15300.00, 'pending', 'unpaid',
  'Couple friendly cottage required.', 'Awaiting deposit verification.', 1, NOW(), NOW()
),
(
  3, 'BK-2026-00003', 3, 3, DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY),
  1, 0, 0, 6500.00, 4000.00, 6500.00,
  'none', 0.00, 0.00, 6500.00,
  0.00, 6500.00, 'completed', 'unpaid',
  'Window seat on train.', 'Tour completed successfully.', 1, NOW(), NOW()
);


