# AMA Computer College Ã¢â‚¬â€ Practicum Management System

Pure PHP 8 + PDO/MySQL web application for managing On-the-Job Training (OJT): enrollment, pre-deployment requirements, host training establishment coordination, DTR/weekly reports, evaluations, and live chat.

**Repository:** [github.com/kumaru06/practicummanagementsystem](https://github.com/kumaru06/practicummanagementsystem)

---

## Roles

| Role | Description |
|------|-------------|
| **Admin** | Users, coordinators, host training establishments, programs, email logs |
| **OJT Coordinator** | Create students from COR, enrollments, requirement review, deployments |
| **Student** | Profile, documents, DTR, weekly reports, timeline, chat |
| **Host Training Establishment** | Portal, submissions review, orientation, evaluations, chat |

---

## Tech stack

- PHP 8+
- MySQL (PDO)
- PHPMailer (SMTP)
- Dompdf (PDF Ã¢â‚¬â€ endorsement letters, reports)
- Vanilla JavaScript + CSS (no frontend framework)

---

## Project structure

```
amaccmanagementsystem/
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ api/                    # Async JSON endpoints (e.g. live chat)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ assets/
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ css/                # Global, login, and chat styles
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ js/                 # main.js, chat.js, login-portal.js, Ã¢â‚¬Â¦
Ã¢â€â€š   Ã¢â€â€Ã¢â€â‚¬Ã¢â€â‚¬ image/              # Logos and static images
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ bootstrap/              # Env loader, mailer bootstrap
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ config/                 # database.php, mail.php (+ local *.example files)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ controllers/            # Request handlers (Admin, Auth, Coordinator, Ã¢â‚¬Â¦)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ database/
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ schema.sql          # Base schema
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ seed.sql            # Seed data
Ã¢â€â€š   Ã¢â€â€Ã¢â€â‚¬Ã¢â€â‚¬ migration_*.sql     # Incremental migrations (run in order)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ lib/                    # Bundled libraries (PHPMailer copy)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ models/                 # Data access (User, Student, Enrollment, Ã¢â‚¬Â¦)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ template/               # Document templates (e.g. consent form)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ tools/                  # SMTP test, deploy helpers
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ uploads/                # User uploads (COR, requirements, signatures) Ã¢â‚¬â€ not in git
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ vendor/                 # Composer dependencies (dompdf, phpmailer, Ã¢â‚¬Â¦)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ views/
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ admin/              # Admin dashboards and management screens
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ coordinator/        # Coordinator dashboards, manage, students
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ partner/            # Host training establishment portal and submissions
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ student/            # Student portal pages
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ chat/               # Live chat UI
Ã¢â€â€š   Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ emails/             # Email HTML templates
Ã¢â€â€š   Ã¢â€â€Ã¢â€â‚¬Ã¢â€â‚¬ shared/             # Header, footer, login layout
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ auth.php                # Login entry point
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ index.php               # Main router (pretty URLs + actions)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ init.php                # App bootstrap, session, autoload
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ helpers.php             # Shared helpers (upload, CSRF, routes, Ã¢â‚¬Â¦)
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ mail.php                # Mail configuration loader
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ composer.json
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ .env.example            # Copy to .env for local environment variables
Ã¢â€Å“Ã¢â€â‚¬Ã¢â€â‚¬ HOSTINGER_DEPLOY.md     # Production deployment guide
Ã¢â€â€Ã¢â€â‚¬Ã¢â€â‚¬ QUICK_START.md          # Endorsement letter / quick test guide
```

---

## Local setup (Laragon / XAMPP)

1. **Clone the repo**
   ```bash
   git clone https://github.com/kumaru06/practicummanagementsystem.git
   cd practicummanagementsystem
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment**
   - Copy `.env.example` Ã¢â€ â€™ `.env`
   - Copy `config/mail.local.php.example` Ã¢â€ â€™ `config/mail.local.php` (SMTP credentials)
   - Set database credentials in `config/database.php`

4. **Database**
   Import in order:
   - `database/schema.sql`
   - `database/seed.sql`
   - All `database/migration_*.sql` files (by date in filename)

5. **Open the app**
   ```
   http://localhost/amaccmanagementsystem/auth.php
   ```
   (Adjust the path to match your local web root.)

### Seed login

---

## Deployment

See **[HOSTINGER_DEPLOY.md](HOSTINGER_DEPLOY.md)** for Git auto-deploy and zip-based Hostinger setup.

---

## Security notes

- Never commit `.env`, `config/mail.local.php`, `config/mail.local.dev.php`, or `hostinger.deploy.json`.
- All POST forms use CSRF tokens.
- Student COR uploads: PDF/JPG/PNG, max 5MB.
- Create-student form validates duplicate **Student ID/USN** and **email** live and on the server; user + student rows are created in a single database transaction.

---

## License

Internal / academic use Ã¢â‚¬â€ AMA Computer College OJT program.
