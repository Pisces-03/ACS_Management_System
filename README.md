# ACS Management System

A web-based maintenance and employee assignment management system for **Asia Cold Storage Corporation**, built with **Laravel** (PHP) as a capstone project of STI College Las Piñas.

## 🌟 Features

### 🔐 Security
- **Role-based access control:** Superadmin, Admin and Employee, enforced by middleware
- **Two-factor authentication:** one-time code sent by email at login (code expiry, resend delay and attempt limit are configurable)
- **Session timeout:** automatic logout after 2 hours of inactivity, with a warning before logout
- **CSRF protection** on forms and AJAX requests
- **Password hashing** with bcrypt
- **Audit logs:** records important system activities
- **Password recovery:** handled by the system administrator (contact details shown on the login page)

### 📊 Core Modules
- **Dashboard:** role-based overview with key metrics
- **Attendance:** time in / time out logging (Philippine timezone)
- **Task Management:** create, assign, confirm and track maintenance tasks
- **Maintenance Logs:** equipment maintenance records with photo uploads
- **Preventive Maintenance:** scheduled task generation based on service intervals
- **Material Requests:** request and approval workflow (Admin / Superadmin)
- **Customer Feedback:** job feedback and ratings, with reports
- **Notifications:** in-app notifications for tasks, approvals and reminders
- **Employee Management:** user administration (Admin and above)
- **Reports:** maintenance, performance, materials, attendance and customer feedback reports with export
- **Audit Logs:** searchable activity history with export

### 🏢 Multi-branch Support
Branches: **Las Piñas, Cebu, Davao**. Each branch has its own admin and employees, branch-specific tasks and reports. The Superadmin can see all branches.

## 🏗️ Project Structure

```
ACS_Management_System/
├── app/
│   ├── Console/Commands/    # Scheduled commands (overdue checks, preventive maintenance)
│   ├── Http/
│   │   ├── Controllers/     # Application controllers
│   │   └── Middleware/      # Role and two-factor middleware
│   ├── Models/              # Eloquent models
│   ├── Notifications/       # Notification classes
│   ├── Policies/            # Authorization policies
│   ├── Providers/
│   ├── Rules/               # Custom validation rules
│   └── Services/            # Business logic (notifications, branch analytics)
├── bootstrap/               # Application bootstrap and helper functions
├── config/                  # Configuration files
├── database/
│   ├── migrations/          # Database schema
│   └── seeders/             # Demo data
├── public/                  # Web root (index.php, css, js)
├── resources/views/         # Blade templates
├── routes/                  # web.php, api.php, console.php (scheduler)
├── storage/                 # Logs, cache and uploaded files
├── .env.example             # Environment template
├── artisan                  # Laravel command line tool
└── composer.json            # PHP dependencies
```

## 🚀 Technology Stack

- **PHP** and **Laravel 12**
- **MySQL** database with **Eloquent ORM**
- **Blade** templates, **Bootstrap 5**, **Bootstrap Icons**, **Chart.js** and **SweetAlert2** (loaded from CDN)
- **Brevo (SMTP)** or any SMTP service for email
- **Composer** for dependency management

## 🔧 Installation & Setup

### Prerequisites
- PHP 8.2 or higher (required by Laravel 12)
- Composer
- MySQL 8.0 or higher
- Git

### Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Pisces-03/ACS_Management_System.git
   cd ACS_Management_System
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Set up the environment file:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Create an empty MySQL database** (for example `acs_management_system`), then configure it in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=acs_management_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Configure email in `.env`** (needed for two-factor login codes):
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=your-smtp-username
   MAIL_PASSWORD=your-smtp-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@example.com
   ```

6. **Run migrations and seed demo data:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Link the storage folder** (for uploaded maintenance photos):
   ```bash
   php artisan storage:link
   ```

8. **Start the development server:**
   ```bash
   php artisan serve
   ```
   The application will be available at `http://localhost:8000`.

### Two-Factor Authentication Settings

```env
TWO_FACTOR_CODE_LENGTH=6
TWO_FACTOR_EXPIRES_MINUTES=10
TWO_FACTOR_RESEND_SECONDS=60
TWO_FACTOR_MAX_ATTEMPTS=5
```

### Scheduled Tasks
The scheduler (`routes/console.php`) generates preventive maintenance tasks and checks for overdue tasks and upcoming maintenance. In production, run it every minute with cron:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 👤 Demo Accounts

> **These accounts are for demonstration and testing only.** They are created by `php artisan db:seed` and use a simple shared password. **Change or delete them before using the system in production.**

| Role | Email | Password |
|------|-------|----------|
| Superadmin | admin@acs.com | password |
| Las Piñas Admin | admin@laspinas.acs.com | password |
| Las Piñas Employee | employee@laspinas.acs.com | password |
| Cebu Admin | admin@cebu.acs.com | password |
| Cebu Employee | employee@cebu.acs.com | password |
| Davao Admin | admin@davao.acs.com | password |
| Davao Employee | employee@davao.acs.com | password |

Because login uses a two-factor code sent by email, the demo account email addresses must be able to receive mail, or you can change a seeded account's email to one you control.

## 🗄️ Database Tables

Created by the migrations in `database/migrations`:

- `users` - employee accounts, roles, branch and two-factor data
- `tasks` - task assignments and tracking
- `task_maintenance_schedules` - scheduled maintenance linked to tasks
- `maintenance_logs` - maintenance work records
- `material_requests` - material requests and approvals
- `customer_feedback` - customer ratings and comments
- `attendance_logs` - employee time in / time out
- `notifications` - in-app notifications
- `audit_logs` - system activity history
- `sessions`, `cache`, `jobs` - Laravel framework tables

## 📱 Usage Guide

1. **Log in** with a demo account and enter the two-factor code sent to its email.
2. **Create users** in Employee Management (Admin and above).
3. **Create and assign tasks** to employees of a branch.
4. **Employees** confirm tasks, time in / out and log maintenance work with photos.
5. **Request materials** and approve or reject them (Admin / Superadmin).
6. **Monitor** progress through the dashboards and **generate reports**.

## 🚀 Deployment

1. **Server requirements:** PHP 8.2+ with required extensions, MySQL 8.0+, Apache or Nginx, HTTPS certificate.
2. **Steps:**
   ```bash
   composer install --optimize-autoloader --no-dev
   cp .env.example .env
   php artisan key:generate
   # edit .env: APP_ENV=production, APP_DEBUG=false, database and mail settings
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. **Web server:** point the document root to the `public/` folder, enable HTTPS, and set proper file permissions on `storage/` and `bootstrap/cache/`.

## 👥 Development Team

**STI College Las Piñas - Capstone Project Team:**
- **Shyler C. Alemania** - Lead Developer
- **Joseph Mark Angelo U. Boyagon** - Backend Developer
- **Mark Leonielle B. Duerme** - Frontend Developer
- **Luis Rafael A. Lopez** - Database Administrator

## 📞 Contact

- **Email:** shyleralemania022402@gmail.com
- **Address:** Las Piñas City, Philippines

## 📝 License

This project was developed as a capstone project for Asia Cold Storage Corporation. All rights reserved.

---

*Built with Laravel*
