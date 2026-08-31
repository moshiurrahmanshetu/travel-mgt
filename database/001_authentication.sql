-- ==========================================================
-- Tour & Travel Booking Management System
-- Phase 01: Authentication & System Foundation
-- Migration File: 001_authentication.sql
-- ==========================================================

-- Disable foreign key checks during schema setup
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. Table: roles
-- ----------------------------------------------------------
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

-- Seed Permissions
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`) VALUES
(1, 'View Dashboard', 'dashboard.view', 'Access to system overview and metrics'),
(2, 'View Profile', 'profile.view', 'View own account profile details'),
(3, 'Edit Profile', 'profile.edit', 'Modify own account profile details'),
(4, 'Change Password', 'password.change', 'Update own account password'),
(5, 'View Users', 'users.view', 'View list of system staff and users'),
(6, 'Create Users', 'users.create', 'Add new user accounts'),
(7, 'Edit Users', 'users.edit', 'Update existing user accounts'),
(8, 'Delete Users', 'users.delete', 'Soft-delete or deactivate user accounts');

-- Assign Permissions to Administrator (All permissions)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5),
(3, 1), (3, 2), (3, 3), (3, 4);

-- Seed Default Administrator
-- Default credentials:
-- Email: admin@example.com
-- Password: Admin@12345
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
