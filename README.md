# Tour & Travel Booking Management System

A Tour & Travel Booking Management System built with **PURE RAW PHP**, MySQL, PDO, Bootstrap 5, and Vanilla JavaScript.

---

## 📌 Phase 01: Project Foundation + Authentication System

Phase 01 implements the core system architecture, database layer, authentication engine, session management, role/permission foundation, collapsible admin layout, and user profile management.

> **Architecture Notice:** This is **NOT** a Laravel or MVC framework project. It uses a clean, maintainable, modular Raw PHP directory convention suitable for commercial PHP web applications.

---

## 🚀 Technology Stack

- **Backend:** Pure Raw PHP (PHP 8.0+)
- **Database:** MySQL 5.7+ / MariaDB 10.3+ with PDO (InnoDB engine)
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
│   └── database.sql          # Complete Phase 01 database creation and seed script
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
│   │   └── index.php         # Admin dashboard overview with KPI metric placeholders
│   │
│   ├── profile/
│   │   ├── index.php         # User profile view and update forms
│   │   ├── update.php        # Profile information update processor
│   │   ├── upload-avatar.php # Secure avatar image upload processor
│   │   └── change-password.php # Password change form and processor
│   │
│   ├── users/
│   │   └── index.php         # Users & Roles foundation overview
│   │
│   └── settings/
│       └── index.php         # Application settings foundation view
│
├── uploads/
│   └── avatars/              # Storage directory for user profile avatars
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
3. **CSRF Protection:** Synchronizer token pattern with `hash_equals()` validation on all state-changing POST forms (login, profile update, avatar upload, password change).
4. **Session Hardening:** Session IDs are regenerated via `session_regenerate_id(true)` upon successful authentication. Session cookies use `HttpOnly`, `SameSite=Lax`, and secure flags.
5. **Secure Avatar Uploads:** MIME-type validation via `finfo`, file extension allowlist (`jpg`, `jpeg`, `png`, `webp`), size restriction (2MB max), image dimension verification (`getimagesize`), and randomized filename generation.
6. **Soft Deletion & Account Status Verification:** Inactive or soft-deleted accounts (`deleted_at IS NOT NULL`) are immediately denied access and terminated from active sessions.
7. **Generic Error Messages:** Authentication failures return generic messages to prevent user enumeration.

---

## 🧭 Navigation & Module Roadmap

- **Dashboard:** Operational (`modules/dashboard/index.php`)
- **My Profile:** Operational (`modules/profile/index.php`)
- **Avatar Upload:** Operational (`modules/profile/upload-avatar.php`)
- **Change Password:** Operational (`modules/profile/change-password.php`)
- **Users & Roles Foundation:** Operational (`modules/users/index.php`)
- **Settings Foundation:** Operational (`modules/settings/index.php`)
- **Future Modules (Phases 02–06):** Tour Packages, Customers, Bookings, Payments, Reports are marked as *Coming Soon* in the navigation.