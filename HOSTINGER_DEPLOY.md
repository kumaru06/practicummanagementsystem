# Hostinger Deployment Guide

Two ways to deploy **ama-ojtportal.com** without broken folders or missing `assets/`.

---

## Option A — Git auto-deploy (recommended)

Best for ongoing updates: push code → Hostinger deploys automatically.

### One-time setup

1. Push this project to GitHub (already: `github.com/kumaru06/practicummanagementsystem`).
2. In **Hostinger hPanel** → **Websites** → **ama-ojtportal.com** → **Advanced** → **Git**.
3. **Create repository** → connect GitHub → select your repo and branch (`main`).
4. Set **Install path** to: `public_html`
5. Turn on **Auto deployment**.
6. On the server (once), create `public_html/config/database.production.php` with MySQL credentials from hPanel (copy from `config/database.production.php.example`).

### Every update

```powershell
cd C:\xampp\htdocs\amaccmanagementsystem
git add .
git commit -m "Describe your change"
git push
```

Hostinger pulls the latest code within a minute. No zip, no File Manager.

**Note:** `config/database.production.php` is in `.gitignore` — server credentials stay on Hostinger only.

---

## Option B — One-command FTP deploy

Uploads every file with correct folder structure (no zip extract issues).

### One-time setup

1. hPanel → **Files** → **FTP Accounts** → note host, username, password.
2. Copy the example config:

   ```powershell
   cd C:\xampp\htdocs\amaccmanagementsystem
   copy hostinger.deploy.json.example hostinger.deploy.json
   ```

3. Edit `hostinger.deploy.json` with your FTP details.

### Deploy

**Easiest:** double-click **`deploy.bat`** in the project folder.

Or in PowerShell (note the `.\` prefix):

```powershell
cd C:\xampp\htdocs\amaccmanagementsystem
.\hostinger-deploy.ps1
```

**First time only:** double-click **`setup-hostinger.bat`** to create `hostinger.deploy.json` and enter your FTP password.

This uploads all app files to `public_html` via FTP. It **skips**:

- `config/database.production.php` (keeps server DB password safe)
- User files in `uploads/`
- `.git`, zip files, SQL exports

### Zip only (manual upload fallback)

```powershell
.\hostinger-deploy.ps1 -ZipOnly
```

Creates `C:\xampp\htdocs\amaccmanagementsystem_deploy.zip` with forward-slash paths. Upload to `public_html` and extract in File Manager.

---

## First-time server checklist

| Step | Where |
|------|--------|
| MySQL database + user | hPanel → Databases |
| `config/database.production.php` | File Manager or FTP |
| Import data | phpMyAdmin → `database/schema.sql` + migrations, or your export |
| SSL + Force HTTPS | hPanel → SSL |
| PHP 8.1+ | hPanel → PHP Configuration |

---

## Verify after deploy

- `https://ama-ojtportal.com/auth.php` — styled login page
- `https://ama-ojtportal.com/assets/css/style.css` — shows CSS (not HTML)

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Unstyled login / broken UI | `assets/` missing — run `hostinger-deploy.ps1` again |
| **Email failed / PHPMailer not installed** | **`vendor/` is incomplete on server** — see below |
| "Password was reset, but the email failed" | Password *did* change in DB; email did not send — fix `vendor/` first, then click **Resend** |
| HTTP 500 | Check `config/database.production.php` credentials |
| FTP fails | Reset FTP password in hPanel, update `hostinger.deploy.json` |
| Git deploy overwrites DB config | Keep `database.production.php` only on server (gitignored) |

### Fix missing PHPMailer (email not sending)

Symptoms: Email Logs show `PHPMailer is not installed`, or Resend shows the red popup.

The app loads PHPMailer from `lib/phpmailer/` via `bootstrap/mailer.php` (included in git). On Hostinger, `vendor/phpmailer/` may be missing even when `vendor/autoload.php` exists.

**Option 1 — FTP full deploy (recommended)**

Run **`setup-hostinger.bat`**, then **`deploy.bat`** — uploads `lib/`, `bootstrap/`, and all app files.

**Option 2 — Mailer-only zip (~62 KB)**

Double-click **`deploy-mailer.bat`**, upload `mailer-only.zip` to `public_html`, extract. Ensures `lib/phpmailer/` and `bootstrap/mailer.php` are present.

**Option 3 — Full vendor zip (4 MB)**

Double-click **`deploy-vendor.bat`** if you also need dompdf/PDF features.

After deploy: Admin → **Resend** → Email Logs should show **Sent**.
