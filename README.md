# Tour & Travel Booking Management System

A Tour & Travel Booking Management System built with **PURE RAW PHP**, MySQL, PDO, Bootstrap 5, and Vanilla JavaScript.

---

## 📌 Phase Overview

- **Phase 01:** Project Foundation, Core Layout, Database Layer & Authentication Engine
- **Phase 02:** Avatar Upload Engine Fix + Tour Package Management (Categories, Destinations, Packages, Gallery & Multi-Day Itineraries)

> **Architecture Notice:** This is **NOT** a Laravel or MVC framework project. It uses a clean, maintainable, modular Raw PHP directory convention suitable for commercial PHP web applications.

---

## 🚀 Technology Stack

- **Backend:** Pure Raw PHP (PHP 8.0+)
- **Database:** MySQL 5.7+ / MariaDB 10.3+ with PDO (InnoDB engine with strict FK safety)
- **Frontend:** HTML5, CSS3 (Strictly solid colors, zero gradients), Vanilla JavaScript
- **CSS Framework:** Bootstrap 5.3.3 & Bootstrap Icons 1.11.3
- **Server Environment:** XAMPP (Apache + MySQL)

---

## 📁 Project Directory Structure

```
travel-mgt/
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Public & login styling (solid colors)
│   │   └── admin.css         # Admin panel & collapsible sidebar styling
│   │
│   ├── js/
│   │   ├── main.js           # Public interactions & password toggling
│   │   └── admin.js          # Sidebar collapse, localStorage persistence, tooltips
│   │
│   ├── images/
│   └── vendor/
│
├── auth/
│   ├── login.php             # Login view
│   ├── logout.php            # Session destruction & logout handler
│   └── process-login.php     # Authentication & credential verification handler
│
├── config/
│   ├── config.php            # App configuration, timezone, URLs, and session start
│   └── database.php          # Centralized PDO connection handler
│
├── database/
│   ├── 001_authentication.sql# Phase 01 authentication & role schema migration
│   ├── 002_tour_management.sql # Phase 02 tour package management schema migration
│   └── database.sql          # Complete cumulative database creation and seed script
│
├── includes/
│   ├── auth_check.php        # Reusable authentication guard
│   ├── functions.php         # Core reusable helper functions
│   ├── csrf.php              # CSRF token generation and validation
│   ├── flash.php             # Flash notification alerts system
│   ├── header.php            # Public HTML header
│   ├── footer.php            # Public HTML footer
│   ├── admin_header.php      # Admin layout head and styles
│   ├── admin_sidebar.php     # Admin collapsible sidebar navigation
│   ├── admin_topbar.php      # Admin topbar with toggle and user profile menu
│   └── admin_footer.php      # Admin layout footer and scripts
│
├── modules/
│   ├── dashboard/
│   │   └── index.php         # Admin dashboard overview with live metric stats
│   │
│   ├── profile/
│   │   ├── index.php         # User profile view and update forms
│   │   ├── update.php        # Profile information update processor
│   │   ├── upload-avatar.php # Secure avatar image upload processor (Fixed)
│   │   └── change-password.php # Password change form and processor
│   │
│   ├── tours/
│   │   ├── index.php         # Tour packages listing with search & filters
│   │   ├── create.php        # Tour package creation form & dynamic itinerary builder
│   │   ├── store.php         # Tour package store processor (transactional)
│   │   ├── view.php          # Full tour package detail view & gallery
│   │   ├── edit.php          # Tour package editor
│   │   ├── update.php        # Tour package update processor
│   │   ├── delete.php        # Tour package soft-delete processor
│   │   ├── delete-image.php  # Individual gallery image deletion processor
│   │   ├── categories.php    # Tour categories management
│   │   ├── category-store.php# Category create processor
│   │   ├── category-update.php# Category update processor
│   │   ├── category-delete.php# Safe category delete processor (dependency checked)
│   │   ├── destinations.php  # Tour destinations management
│   │   ├── destination-store.php # Destination create processor
│   │   ├── destination-update.php # Destination update processor
│   │   └── destination-delete.php # Safe destination delete processor (dependency checked)
│   │
│   ├── users/
│   │   └── index.php         # Users & Roles foundation overview
│   │
│   └── settings/
│       └── index.php         # Application settings foundation view
│
├── uploads/
│   ├── avatars/              # Storage directory for user profile avatars
│   ├── tours/                # Storage directory for tour cover & gallery images
│   └── destinations/         # Storage directory for destination cover images
│
├── .htaccess                 # Apache server configuration and security
├── index.php                 # Root entry router (redirects to dashboard or login)
└── README.md                 # Project documentation
```

---

## 🛠️ Setup & Installation (XAMPP)

### 1. Place Project in XAMPP
Ensure the project folder is placed inside your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\travel-mgt
```

### 2. Start Apache and MySQL
Open the **XAMPP Control Panel** and start both **Apache** and **MySQL** services.

### 3. Import Database
You can import the database using either **phpMyAdmin** or the **MySQL CLI**:

#### Option A: phpMyAdmin
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Click **Import**.
3. Choose the file: `c:\xampp\htdocs\travel-mgt\database\database.sql`.
4. Click **Go** to create the `travel_mgt_db` database and seed tables.

#### Option B: MySQL Command Line
```powershell
Get-Content c:\xampp\htdocs\travel-mgt\database\database.sql | & "C:\xampp\mysql\bin\mysql.exe" -u root
```

---

## 🔑 Default Development Admin Credentials

| Credential | Value |
| :--- | :--- |
| **Login URL** | `http://localhost/travel-mgt/auth/login.php` |
| **Email** | `admin@example.com` |
| **Password** | `Admin@12345` |
| **Role** | Administrator |

> ⚠️ **Security Warning:** The default administrator password is for development setup. Please change your password via **My Profile &rarr; Change Password** immediately upon deploying to any staging or live server.

---

## 🛡️ Security Features Implemented

1. **Prepared Statements (PDO):** All database interactions use parameterized queries to eliminate SQL Injection risks.
2. **Bcrypt Password Hashing:** Passwords are hashed and verified using PHP's native `password_hash()` and `password_verify()` with default bcrypt cost. Plaintext passwords are never stored.
3. **CSRF Protection:** Synchronizer token pattern with `hash_equals()` validation on all state-changing POST forms (login, profile update, avatar upload, password change, tour create/edit/delete, category create/edit/delete, destination create/edit/delete).
4. **Session Hardening:** Session IDs are regenerated via `session_regenerate_id(true)` upon successful authentication. Session cookies use `HttpOnly`, `SameSite=Lax`, and secure flags.
5. **Secure Avatar & Image Uploads:** Full validation via `is_uploaded_file()`, `getimagesize()`, MIME allowlisting via `finfo` (`image/jpeg`, `image/pjpeg`, `image/png`, `image/x-png`, `image/webp`), size enforcement, safe randomized filename generation, and safe post-update unlinking of old files.
6. **Soft Deletion & Foreign Key Safety:** Tour packages, categories, and destinations use soft deletes (`deleted_at`). Categories and destinations cannot be deleted if assigned to active tour packages.
7. **Role-Based Access Control (RBAC):** Server-side permission guards (`require_permission()`) on all Tour, Category, and Destination actions.

---

## 🧭 Navigation & Module Status

- **Dashboard:** Operational (`modules/dashboard/index.php`)
- **Tour Packages:** Operational (`modules/tours/index.php`)
- **Tour Categories:** Operational (`modules/tours/categories.php`)
- **Tour Destinations:** Operational (`modules/tours/destinations.php`)
- **My Profile:** Operational (`modules/profile/index.php`)
- **Avatar Upload:** Operational (`modules/profile/upload-avatar.php`)
- **Change Password:** Operational (`modules/profile/change-password.php`)
- **Users & Roles Foundation:** Operational (`modules/users/index.php`)
- **Settings Foundation:** Operational (`modules/settings/index.php`)
- **Future Modules (Phases 03–06):** Customers, Bookings, Payments, Reports are marked as *Coming Soon* in the navigation.