-- ============================================================================
-- Tour & Travel Booking Management System
-- Phase 05: Payment Management Schema & Seed Data
-- ============================================================================

USE `travel_mgt_db`;

-- ----------------------------------------------------------------------------
-- Table: payments
-- Description: Stores customer booking payment transactions, payment methods, and statuses
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_number` VARCHAR(30) NOT NULL,
    `booking_id` BIGINT UNSIGNED NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('cash', 'bank_transfer', 'card', 'mobile_banking', 'other') NOT NULL DEFAULT 'cash',
    `transaction_id` VARCHAR(100) NULL DEFAULT NULL,
    `payment_status` ENUM('completed', 'pending', 'failed', 'refunded') NOT NULL DEFAULT 'completed',
    `notes` TEXT NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
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

-- ----------------------------------------------------------------------------
-- Permissions for Payment Management (Phase 05)
-- ----------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`)
VALUES
    (32, 'View Payments', 'payments.view', 'Can view payment transactions and receipts', NOW(), NOW()),
    (33, 'Create Payments', 'payments.create', 'Can record new customer payments', NOW(), NOW()),
    (34, 'Edit Payments', 'payments.edit', 'Can modify payment transaction records', NOW(), NOW()),
    (35, 'Delete Payments', 'payments.delete', 'Can soft-delete payment transactions', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- Role Permissions Bindings for Payment Management
-- ----------------------------------------------------------------------------
-- Administrator (Role 1): Full Payment Access (32-35)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (1, 32), (1, 33), (1, 34), (1, 35);

-- Manager (Role 2): View, Create, Edit, Delete
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 32), (2, 33), (2, 34), (2, 35);

-- Staff (Role 3): View and Create
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 32), (3, 33);

-- ----------------------------------------------------------------------------
-- Seed Data: Sample Payment Transactions
-- ----------------------------------------------------------------------------
INSERT INTO `payments` (
    `id`, `payment_number`, `booking_id`, `payment_date`,
    `amount`, `payment_method`, `transaction_id`, `payment_status`,
    `notes`, `created_by`, `created_at`, `updated_at`
) VALUES
(
    1, 'PAY-2026-00001', 1, CURRENT_DATE(),
    15000.00, 'bank_transfer', 'TRX-CITY-889922', 'completed',
    'Initial 50% deposit received via City Bank transfer.', 1, NOW(), NOW()
),
(
    2, 'PAY-2026-00002', 3, DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY),
    6500.00, 'cash', NULL, 'completed',
    'Full payment received in cash at office counter.', 1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `payment_number` = VALUES(`payment_number`), `amount` = VALUES(`amount`);

-- Synchronize Sample Booking Payment Summaries
UPDATE `bookings` SET `paid_amount` = 15000.00, `due_amount` = 16500.00, `payment_status` = 'partial' WHERE `id` = 1;
UPDATE `bookings` SET `paid_amount` = 0.00, `due_amount` = 15300.00, `payment_status` = 'unpaid' WHERE `id` = 2;
UPDATE `bookings` SET `paid_amount` = 6500.00, `due_amount` = 0.00, `payment_status` = 'paid' WHERE `id` = 3;
