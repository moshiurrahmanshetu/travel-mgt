-- ============================================================================
-- Tour & Travel Booking Management System
-- Phase 06: Reports & Analytics Permissions & Database Optimization
-- ============================================================================

USE `travel_mgt_db`;

-- ----------------------------------------------------------------------------
-- Permissions for Reports & Dashboard Analytics (Phase 06)
-- ----------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`)
VALUES
    (36, 'View Reports', 'reports.view', 'Can view management reports and business analytics dashboards', NOW(), NOW()),
    (37, 'Export Reports', 'reports.export', 'Can download and export report datasets to CSV spreadsheets', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- ----------------------------------------------------------------------------
-- Role Permissions Bindings for Reports & Analytics
-- ----------------------------------------------------------------------------
-- Administrator (Role 1): Full Access (36, 37)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (1, 36), (1, 37);

-- Manager (Role 2): View & Export (36, 37)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 36), (2, 37);

-- Staff (Role 3): View Reports Only (36)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 36);
