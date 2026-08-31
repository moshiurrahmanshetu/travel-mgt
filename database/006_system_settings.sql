-- ============================================================================
-- Tour & Travel Booking Management System
-- Phase 07: System Settings & RBAC Permissions Migration
-- Migration File: 006_system_settings.sql
-- ============================================================================

USE `travel_mgt_db`;

-- ----------------------------------------------------------------------------
-- 1. Alter roles table: Add is_system column if not exists
-- ----------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'travel_mgt_db' 
      AND TABLE_NAME = 'roles' 
      AND COLUMN_NAME = 'is_system'
);

SET @sql := IF(@col_exists = 0, 'ALTER TABLE `roles` ADD COLUMN `is_system` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mark core system roles
UPDATE `roles` SET `is_system` = 1 WHERE `slug` IN ('administrator', 'manager', 'staff');

-- ----------------------------------------------------------------------------
-- 2. Create settings Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Seed Default Application Settings
-- ----------------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'GlobeTrek Travels & Tours'),
('company_email', 'info@globetrektravels.com'),
('company_phone', '+880 1700-000000'),
('company_address', 'Level 4, Plot 12, Gulshan-2, Dhaka-1212, Bangladesh'),
('company_website', 'https://www.globetrektravels.com'),
('currency', 'BDT'),
('currency_symbol', '৳'),
('timezone', 'Asia/Dhaka')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);

-- ----------------------------------------------------------------------------
-- 4. Permissions for Roles, Permissions & Settings (Phase 07)
-- ----------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`)
VALUES
    (38, 'View Roles', 'roles.view', 'Can view role list and permission mappings', NOW(), NOW()),
    (39, 'Create Roles', 'roles.create', 'Can create new system user roles', NOW(), NOW()),
    (40, 'Edit Roles', 'roles.edit', 'Can update existing roles and role permissions', NOW(), NOW()),
    (41, 'Delete Roles', 'roles.delete', 'Can soft-delete custom user roles', NOW(), NOW()),
    (42, 'View Permissions', 'permissions.view', 'Can view system permission catalog', NOW(), NOW()),
    (43, 'Assign Permissions', 'permissions.assign', 'Can assign permissions to roles', NOW(), NOW()),
    (44, 'View Settings', 'settings.view', 'Can view system configuration and settings', NOW(), NOW()),
    (45, 'Edit Settings', 'settings.edit', 'Can modify system configuration and company information', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- 5. Role Permission Bindings
-- ----------------------------------------------------------------------------
-- Administrator (Role 1): Full Access (All permissions 1-45)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (1, 38), (1, 39), (1, 40), (1, 41), (1, 42), (1, 43), (1, 44), (1, 45);

-- Manager (Role 2): View Users, Roles, Permissions, Settings
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 5), (2, 38), (2, 42), (2, 44);
