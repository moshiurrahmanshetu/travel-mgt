# Tour & Travel Booking Management System

A Tour & Travel Booking Management System built with **PURE RAW PHP**, MySQL, PDO, Bootstrap 5, and Vanilla JavaScript.

---

## 📌 Phase Overview

- **Phase 01:** Project Foundation, Core Layout, Database Layer & Authentication Engine
- **Phase 02:** Avatar Upload Engine Fix + Tour Package Management (Categories, Destinations, Packages, Gallery & Multi-Day Itineraries)
- **Phase 03:** Customer Management (Profiles, Contact Info, Passports/NID, Photo Uploads, Safe Soft-Delete & Restore)
- **Phase 04:** Booking Management System (Reservations, Dynamic Capacity Tracking, Price Snapshots, Status Transitions, Cancellations, and Customer History)
- **Phase 05:** Payment Management System (Payments Directory, Add Payment, Receipts, Overpayment Protection, Row-Level Locking, Auto Booking Sync, and Revenue Dashboard)

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
│   ├── 003_customer_management.sql # Phase 03 customer management schema migration
│   ├── 004_booking_management.sql  # Phase 04 booking management schema migration
│   ├── 005_payment_management.sql  # Phase 05 payment management schema migration
│   └── database.sql          # Complete cumulative database creation and seed script
│
├── includes/
│   ├── auth_check.php        # Reusable authentication guard
│   ├── functions.php         # Core reusable helper functions, pricing, payments & image validator
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
│   │   └── index.php         # Admin dashboard overview with live revenue and booking stats
│   │
│   ├── profile/
│   │   ├── index.php         # User profile view and update forms
│   │   ├── update.php        # Profile information update processor
│   │   ├── upload-avatar.php # Secure avatar image upload processor
│   │   └── change-password.php # Password change form and processor
│   │
│   ├── tours/
│   │   ├── index.php         # Tour packages listing with search & filters
│   │   ├── create.php        # Tour package creation form & dynamic itinerary builder
│   │   ├── store.php         # Tour package store processor (transactional)
│   │   ├── view.php          # Full tour package detail view, gallery & booking summary
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
│   ├── customers/
│   │   ├── index.php         # Customer directory, search, filter, and pagination
│   │   ├── create.php        # Customer registration form with live photo preview
│   │   ├── store.php         # Customer store processor with auto code generation (CUS-XXXXX)
│   │   ├── view.php          # CRM-style customer profile, payment summary & live booking history
│   │   ├── edit.php          # Customer profile editor
│   │   ├── update.php        # Customer update processor with safe photo replacement
│   │   ├── delete.php        # Customer soft-delete processor
│   │   └── restore.php       # Soft-deleted customer restoration processor
│   │
│   ├── bookings/
│   │   ├── index.php         # Booking directory, search, multi-filter, pagination, cancel modal
│   │   ├── create.php        # Reservation creation form with live Vanilla JS pricing engine
│   │   ├── store.php         # Transactional booking store processor with capacity validation
│   │   ├── view.php          # CRM-style booking detail voucher with live payment history
│   │   ├── edit.php          # Reservation edit form with price snapshot recalculation
│   │   ├── update.php        # Reservation update processor with capacity re-verification
│   │   ├── cancel.php        # POST cancellation processor releasing capacity
│   │   └── status-update.php # Status transition processor (confirm/complete) with capacity checks
│   │
│   ├── payments/
│   │   ├── index.php         # Payment directory, search, status/method filters, pagination, delete modal
│   │   ├── create.php        # Record payment form with live due preview and overpayment guard
│   │   ├── store.php         # Transactional store handler with row locking & overpayment rejection
│   │   ├── view.php          # CRM-style payment transaction receipt / voucher
│   │   ├── edit.php          # Payment remarks and metadata editor (amount immutable)
│   │   ├── update.php        # Payment update processor with automatic booking recalculation
│   │   └── delete.php        # Soft-delete payment processor with automatic booking sync
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
│   ├── destinations/         # Storage directory for destination cover images
│   └── customers/            # Storage directory for customer profile photos
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

## 🛡️ Security & Architecture Rules

1. **Authoritative Source for Payments:** The `payments` table is the single source of truth for financial transactions. Booking fields (`paid_amount`, `due_amount`, `payment_status`) are dynamically synchronized based on completed, non-deleted payment records.
2. **Overpayment Protection:** Server-side validation strictly rejects any completed payment whose amount exceeds the current remaining balance of the booking.
3. **Concurrency Protection with Row-Level Locking:** `store.php` executes inside a database transaction with `SELECT ... FOR UPDATE` locking to prevent simultaneous payments exceeding the booking total.
4. **Immutable Transaction Amounts:** Completed payment amounts are immutable in `edit.php` to preserve accounting audit integrity.
5. **Relational Data Integrity:** Foreign keys use `ON DELETE RESTRICT ON UPDATE CASCADE` to prevent accidental deletion of referenced bookings. Primary keys use stable `BIGINT UNSIGNED` IDs.
6. **CSRF & RBAC:** All state-changing POST requests require valid CSRF tokens and server-side permission verification (`payments.view`, `payments.create`, `payments.edit`, `payments.delete`).

---

## 🧭 Navigation & Module Status

- **Dashboard:** Operational with live revenue, collections, and booking metrics (`modules/dashboard/index.php`)
- **Tour Packages:** Operational (`modules/tours/index.php`)
- **Tour Categories:** Operational (`modules/tours/categories.php`)
- **Tour Destinations:** Operational (`modules/tours/destinations.php`)
- **Customers:** Operational (`modules/customers/index.php`)
- **Bookings:** Operational (`modules/bookings/index.php`)
  - All Bookings (`modules/bookings/index.php`)
  - Pending Bookings (`modules/bookings/index.php?status=pending`)
  - Confirmed Bookings (`modules/bookings/index.php?status=confirmed`)
  - Cancelled Bookings (`modules/bookings/index.php?status=cancelled`)
- **Payments:** Operational (`modules/payments/index.php`)
  - Record Payment (`modules/payments/create.php`)
- **My Profile:** Operational (`modules/profile/index.php`)
- **Avatar Upload:** Operational (`modules/profile/upload-avatar.php`)
- **Change Password:** Operational (`modules/profile/change-password.php`)
- **Users & Roles Foundation:** Operational (`modules/users/index.php`)
- **Settings Foundation:** Operational (`modules/settings/index.php`)
- **Future Modules (Phase 06):** Reports is marked as *Coming Soon* in the navigation.