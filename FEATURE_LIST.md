# AMA Practicum Management System — Feature List

**System:** AMA Computer College OJT / Practicum Management System  
**Version:** 1.0  
**Stack:** PHP 8 · MySQL · PHPMailer · Dompdf  
**Last updated:** June 2026

---

## 1. Authentication & Access Control

| Feature | Description |
|---------|-------------|
| Multi-portal login | Separate login portals for Admin, OJT Coordinator, Student, and Industry Partner |
| Role-based access (RBAC) | Each role sees only its own menus, pages, and actions |
| Portal enforcement | Accounts can only sign in through their assigned portal |
| Session security | Session regeneration on login; logout confirmation |
| First-login password change | New accounts must set a new password before use |
| Account activation | Admin can enable or disable user accounts |
| CSRF protection | All form submissions require a valid CSRF token |

---

## 2. In-App Notifications

| Feature | Description |
|---------|-------------|
| Notification bell | Top-bar menu with unread badge on every authenticated page |
| Event-driven alerts | Automatic notifications for submissions, approvals, rejections, and deployments |
| Deep links | Each notification links to the relevant page |
| Mark as read | Single-click read on open; bulk “Mark all as read” |
| Role-targeted delivery | Notifications sent only to the user who needs to act |

**Notification triggers:** requirement submission · requirement approval/rejection · OJT enrollment · deployment forwarded · DTR submitted · weekly report submitted · DTR/weekly approved or rejected · student evaluations submitted

---

## 3. Email System

| Feature | Description |
|---------|-------------|
| SMTP delivery | Real outbound email via PHPMailer |
| Email logging | Every send attempt recorded in `email_logs` (sent / failed) |
| Admin email logs | Filter logs by type, status, and date |
| Credential emails | Auto-send login details for new coordinators and partners |
| Deployment emails | Forward pre-deployment documents with auto-generated endorsement letter PDF |
| Orientation emails | Instructions and schedule sent to student and coordinator |
| OJT start notification | Email when orientation is completed and OJT officially begins |

---

## 4. User & Account Management (Admin)

| Feature | Description |
|---------|-------------|
| Admin dashboard | Stats and charts: coordinators, partners, students, active enrollments, trends |
| Manage students | View all student accounts |
| Manage coordinators | Create, update coordinators (ID number, department, signature) |
| Manage industry partners | Create partners, assign programs, upload MOA/MOU |
| Resend credentials | Re-send partner login credentials by email |
| Reset credentials | Reset any user’s login credentials |
| Toggle user status | Activate or deactivate accounts |
| Programs & courses | Create/edit/delete programs and required OJT hours |
| Academic terms | Manage term labels used during enrollment |

---

## 5. OJT Coordinator Features

| Feature | Description |
|---------|-------------|
| Coordinator dashboard | Overview of assigned students and activity |
| Student enrollment | Create student accounts and enroll in company/program/term |
| My Students | Review pre-deployment requirements per student |
| Requirement review | Approve or reject individual documents with notes |
| Deployment forwarding | Approve & forward — auto-generates endorsement letter PDF and emails partner |
| Endorsement preview | Preview endorsement letter PDF before forwarding |
| Password reset | Reset student password (email sent to student) |
| Email update | Change student email (notification sent) |
| MOA/MOU library | View partner agreement documents |
| Final requirements | Review student post-OJT documents |
| Evaluations | View student evaluations of partner and coordinator |

---

## 6. Student Features

| Feature | Description |
|---------|-------------|
| Student dashboard | Deployment status, progress, and next-step guidance |
| Profile completion | Photo, contact info, emergency/guardian details, COR upload |
| Pre-deployment documents | Upload 5 required files (consent, PhilHealth, vaccine card, guardian ID, COR) |
| Bulk upload | Upload multiple requirement files in one submission |
| Submit for review | Submit all uploaded requirements to coordinator |
| Final requirements | Job Description · Company Profile · Personal Observation |
| Other documents | View COR and submitted weekly report files |
| Daily Time Records (DTR) | Log time in/out, hours, tasks; save drafts |
| Weekly reports | Submit weekly PDF reports |
| Reports export | Export report summary as PDF |
| Activity timeline | Chronological view of DTR and weekly submissions |
| Evaluations | Rate Industry Partner/Supervisor and OJT Coordinator |
| Settings | Change password |

**Pre-deployment requirements:** Parent/Guardian Consent · PhilHealth · Vaccine Card · Guardian Valid ID · Certificate of Registration (COR)

---

## 7. Industry Partner Features

| Feature | Description |
|---------|-------------|
| Partner dashboard | Overview of deployed students |
| Industry Partner Portal | Accept forwarded deployments; manage orientation workflow |
| View endorsement letter | On-demand PDF viewing (regenerated from database) |
| Orientation workflow | Send instructions · schedule orientation · mark OJT as started |
| Student submissions | Review DTR and weekly reports per student |
| Approve / reject records | Decision with optional notes; student and coordinator notified |
| Student evaluation | Weighted criteria (Work Performance + Personality Traits), grade, comments, certificate upload |

---

## 8. OJT Deployment Workflow

| Stage | Status | Actor |
|-------|--------|-------|
| Upload documents | `not_submitted` | Student |
| Submit for review | `submitted` | Student |
| Coordinator review | `approved` / `needs_revision` | Coordinator |
| Forward to partner | `forwarded` | Coordinator |
| Partner acceptance | `accepted` | Industry Partner |
| Orientation scheduled | `orientation_scheduled` | Industry Partner |
| OJT officially started | `orientation_completed` | Industry Partner |

---

## 9. Evaluations

| Direction | Evaluator | Evaluated | Criteria |
|-----------|-----------|-----------|----------|
| Partner → Student | Industry Partner | Student | Work Performance (50%) + Personality Traits (50%) = 100% |
| Student → Partner | Student | Industry Partner & Supervisor | 16 weighted criteria |
| Student → Coordinator | Student | OJT Coordinator | 8 weighted criteria |

Admins and coordinators can view evaluation results across the system.

---

## 10. Document & File Handling

| Feature | Description |
|---------|-------------|
| File type validation | PDF, JPG, PNG enforced per document type |
| Size limits | COR and weekly reports: 5 MB · bulk uploads: 8 MB |
| Per-file review status | `pending` → `uploaded` → `approved` / `rejected` |
| Selective re-upload | Rejected files can be replaced without re-submitting all documents |
| Endorsement letter PDF | Generated in memory (Dompdf); not stored on disk |
| MOA/MOU storage | Partner agreement files viewable by admin and coordinator |

---

## 11. Security & Compliance

- Password hashing (`password_hash`)
- Role guards on every controller action
- Partner record ownership checks (company-scoped access)
- HTML output escaping (`htmlspecialchars`)
- Open redirect prevention on notification links
- Email attempt audit trail

---

## 12. User Interface

- Responsive layout with collapsible sidebar
- Role-themed navigation (Admin · Coordinator · Student · Partner)
- Flash messages for success and error feedback
- Progress bars for requirement completion
- Status badges for deployment and document states
- Confirmation dialogs (e.g., logout)

---

*For setup instructions, see [README.md](README.md). For endorsement letter details, see [ENDORSEMENT_LETTER_VIEWING.md](ENDORSEMENT_LETTER_VIEWING.md).*
