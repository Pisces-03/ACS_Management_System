# ACS Management System

A web-based maintenance and employee assignment management system for **Asia Cold Storage Corporation**, built with **Laravel** (PHP) as a capstone project of STI College Las Piñas.

## 🌟 Features

- **Role-based access:** Superadmin, Admin and Employee
- **Two-factor login:** a one-time code is sent by email
- **Session timeout:** automatic logout after 2 hours of inactivity, with a warning
- **Attendance:** time in / time out logging
- **Task Management:** create, assign, confirm and track maintenance tasks
- **Maintenance Logs:** equipment maintenance records with photo and video uploads
- **Preventive Maintenance:** scheduled task generation based on service intervals
- **Material Requests:** request and approval workflow (Admin / Superadmin)
- **Customer Feedback:** job ratings and comments, with reports
- **Notifications:** alerts for tasks, approvals, overdue work and reminders
- **Employee Management:** user administration (Admin and above)
- **Reports:** maintenance, performance, materials, attendance and feedback reports, with export (CSV for Excel, printable PDF view)
- **Audit Logs:** searchable activity history with export
- **Multi-branch:** Las Piñas, Cebu and Davao

## 🚀 Technology Stack

- PHP and Laravel 12
- MySQL
- Blade templates, Bootstrap 5
- Composer

## 🔧 Installation

**Requirements:** PHP 8.2+, Composer, MySQL 8.0+, Git

1. Clone the repository:
   ```bash
   git clone https://github.com/Pisces-03/ACS_Management_System.git
   cd ACS_Management_System
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create the environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Create an empty MySQL database (for example `acs_management_system`), then set the database and email (SMTP) details in `.env`.

5. Create the tables and demo data:
   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan storage:link
   ```

6. Start the server:
   ```bash
   php artisan serve
   ```
   Open `http://localhost:8000`.

## 👤 Demo Accounts

> For demonstration and testing only. Change or delete them before real use.

| Role | Email | Password |
|------|-------|----------|
| Superadmin | admin@acs.com | password |
| Las Piñas Admin | admin@laspinas.acs.com | password |
| Las Piñas Employee | employee@laspinas.acs.com | password |
| Cebu Admin | admin@cebu.acs.com | password |
| Cebu Employee | employee@cebu.acs.com | password |
| Davao Admin | admin@davao.acs.com | password |
| Davao Employee | employee@davao.acs.com | password |

Login uses a two-factor code sent by email, so the account's email must be able to receive mail.

## 👥 Development Team

**STI College Las Piñas - Capstone Project Team:**
- Shyler C. Alemania - Lead Developer
- Joseph Mark Angelo U. Boyagon - Backend Developer
- Mark Leonielle B. Duerme - Frontend Developer
- Luis Rafael A. Lopez - Database Administrator

## 📞 Contact

- Email: shyleralemania022402@gmail.com
- Address: Las Piñas City, Philippines

## 📝 License

Developed as a capstone project for Asia Cold Storage Corporation. All rights reserved.
