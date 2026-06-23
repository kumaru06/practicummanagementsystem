# AMA Computer College — Practicum Management System

Pure PHP 8 + PDO/MySQL web application for managing On-the-Job Training (OJT): enrollment, pre-deployment requirements, industry partner coordination, DTR/weekly reports, evaluations, and live chat.

**Repository:** [github.com/kumaru06/practicummanagementsystem](https://github.com/kumaru06/practicummanagementsystem)

---

## Roles

| Role | Description |
|------|-------------|
| **Admin** | Users, coordinators, industry partners, programs, email logs |
| **OJT Coordinator** | Create students from COR, enrollments, requirement review, deployments |
| **Student** | Profile, documents, DTR, weekly reports, timeline, chat |
| **Industry Partner** | Portal, submissions review, orientation, evaluations, chat |

---

## Tech stack

- PHP 8+
- MySQL (PDO)
- PHPMailer (SMTP)
- Dompdf (PDF — endorsement letters, reports)
- Vanilla JavaScript + CSS (no frontend framework)

---

## Project structure

```
amaccmanagementsystem/
├── api/                    # Async JSON endpoints (e.g. live chat)
├── assets/
│   ├── css/                # Global, login, and chat styles
│   ├── js/                 # main.js, chat.js, login-portal.js, …
│   └── image/              # Logos and static images
├── bootstrap/              # Env loader, mailer bootstrap
├── config/                 # database.php, mail.php (+ local *.example files)
├── controllers/            # Request handlers (Admin, Auth, Coordinator, …)
├── database/
│   ├── schema.sql          # Base schema
│   ├── seed.sql            # Seed data
│   └── migration_*.sql     # Incremental migrations (run in order)
├── lib/                    # Bundled libraries (PHPMailer copy)
├── models/                 # Data access (User, Student, Enrollment, …)
├── template/               # Document templates (e.g. consent form)
├── tools/                  # SMTP test, deploy helpers
├── uploads/                # User uploads (COR, requirements, signatures) — not in git
├── vendor/                 # Composer dependencies (dompdf, phpmailer, …)
├── views/
│   ├── admin/              # Admin dashboards and management screens
│   ├── coordinator/        # Coordinator dashboards, manage, students
│   ├── partner/            # Industry partner portal and submissions
│   ├── student/            # Student portal pages
│   ├── chat/               # Live chat UI
│   ├── emails/             # Email HTML templates
│   └── shared/             # Header, footer, login layout
├── auth.php                # Login entry point
├── index.php               # Main router (pretty URLs + actions)
├── init.php                # App bootstrap, session, autoload
├── helpers.php             # Shared helpers (upload, CSRF, routes, …)
├── mail.php                # Mail configuration loader
├── composer.json
├── .env.example            # Copy to .env for local environment variables
├── HOSTINGER_DEPLOY.md     # Production deployment guide
└── QUICK_START.md          # Endorsement letter / quick test guide
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
   - Copy `.env.example` → `.env`
   - Copy `config/mail.local.php.example` → `config/mail.local.php` (SMTP credentials)
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

Internal / academic use — AMA Computer College OJT program.
