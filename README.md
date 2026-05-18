# AMA Computer College Practicum Management System

Pure PHP 8 + PDO MySQL OJT management system with four roles: Admin, OJT Coordinator, Student, and Industry Partner.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Import the database files in phpMyAdmin or MySQL CLI:
   - `database/schema.sql`
   - `database/seed.sql`
   - If updating an existing database, run these after the original schema/seed instead:
     - `database/migration_2026_05_02_features.sql`
     - `database/migration_2026_05_03_deployment_flow.sql`
   - `database/migration_2026_05_11_coordinator_id_number.sql`
   - On shared hosting like InfinityFree, select your database first, then import the SQL files. Do not use SQL files with `CREATE DATABASE` or `USE ...` statements.
3. Confirm database credentials in `config/database.php`.
4. Configure real SMTP credentials in `config/mail.php`.
   - Gmail requires an App Password.
   - PHPMailer is installed through Composer in `vendor/`.
5. Open: `http://localhost/practicum-system/auth.php`

## Seed login

- Admin: `admin@ama.edu.ph` / `Admin@123`

## Notes

- COR uploads are validated as PDF/JPG/PNG and limited to 5MB.
- Weekly report uploads are validated as PDF and limited to 5MB.
- Email sending is real PHPMailer SMTP and every attempt is logged in `email_logs`.
- Internal notifications are stored in `notifications` and shown in the top notification menu.
- Student deployment emails can include forwarded requirement files and the coordinator endorsement letter as attachments.
- All POST forms use CSRF tokens.
