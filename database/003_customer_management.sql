-- ============================================================================
-- Tour & Travel Booking Management System
-- Phase 03: Customer Management Schema & Seed Data
-- ============================================================================

USE `travel_mgt_db`;

-- ----------------------------------------------------------------------------
-- Table: customers
-- Description: Stores customer profiles, contact info, and travel credentials
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
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

-- ----------------------------------------------------------------------------
-- Permissions for Customer Management (Phase 03)
-- ----------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`)
VALUES
    (21, 'View Customers', 'customers.view', 'Can view customer directory and profile details', NOW(), NOW()),
    (22, 'Create Customers', 'customers.create', 'Can add and register new customers', NOW(), NOW()),
    (23, 'Edit Customers', 'customers.edit', 'Can update existing customer profiles', NOW(), NOW()),
    (24, 'Delete Customers', 'customers.delete', 'Can soft delete customer profiles', NOW(), NOW()),
    (25, 'Restore Customers', 'customers.restore', 'Can restore soft-deleted customer profiles', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- Role Permissions Bindings for Customer Management
-- ----------------------------------------------------------------------------
-- Administrator (Role 1): Full Customer Access
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (1, 21), (1, 22), (1, 23), (1, 24), (1, 25);

-- Manager (Role 2): View, Create, Edit, Delete
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 21), (2, 22), (2, 23), (2, 24);

-- Staff (Role 3): View and Create
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 21), (3, 22);

-- ----------------------------------------------------------------------------
-- Seed Data: Sample Customers
-- ----------------------------------------------------------------------------
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
)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `phone` = VALUES(`phone`);
