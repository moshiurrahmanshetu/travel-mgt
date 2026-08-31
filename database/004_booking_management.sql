-- ============================================================================
-- Tour & Travel Booking Management System
-- Phase 04: Booking Management Schema & Seed Data
-- ============================================================================

USE `travel_mgt_db`;

-- ----------------------------------------------------------------------------
-- Table: bookings
-- Description: Stores customer tour reservations, traveller counts, pricing snapshots, and status
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
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

-- ----------------------------------------------------------------------------
-- Permissions for Booking Management (Phase 04)
-- ----------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`)
VALUES
    (26, 'View Bookings', 'bookings.view', 'Can view booking list and reservation vouchers', NOW(), NOW()),
    (27, 'Create Bookings', 'bookings.create', 'Can create and process new tour bookings', NOW(), NOW()),
    (28, 'Edit Bookings', 'bookings.edit', 'Can modify existing tour bookings', NOW(), NOW()),
    (29, 'Cancel Bookings', 'bookings.cancel', 'Can cancel tour reservations', NOW(), NOW()),
    (30, 'Confirm Bookings', 'bookings.confirm', 'Can confirm pending tour reservations', NOW(), NOW()),
    (31, 'Complete Bookings', 'bookings.complete', 'Can mark confirmed bookings as completed', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- Role Permissions Bindings for Booking Management
-- ----------------------------------------------------------------------------
-- Administrator (Role 1): Full Booking Access (26-31)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31);

-- Manager (Role 2): View, Create, Edit, Cancel, Confirm, Complete
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 26), (2, 27), (2, 28), (2, 29), (2, 30), (2, 31);

-- Staff (Role 3): View and Create
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 26), (3, 27);

-- ----------------------------------------------------------------------------
-- Seed Data: Sample Bookings
-- ----------------------------------------------------------------------------
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
)
ON DUPLICATE KEY UPDATE `booking_number` = VALUES(`booking_number`), `total_amount` = VALUES(`total_amount`);
