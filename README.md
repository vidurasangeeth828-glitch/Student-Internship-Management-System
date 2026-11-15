# Student Internship Management System (SIMS)

A responsive PHP/MySQL internship portal for students, companies and administrators. Built using simple procedural PHP, MySQLi prepared statements, HTML forms, sessions, CSS and JavaScript.

Start with `SETUP.md` for installation, administrator setup and workflow checks.

## Pages

| Area | Main pages |
| --- | --- |
| Public | `index.php`, `internships.php`, `internship-details.php`, `companies.php` |
| Authentication | `signup.php`, `signin.php`, `admin-signin.php`, `logout.php` |
| Student | `student-dashboard.php`, `student-profile.php`, `apply.php`, `my-applications.php` |
| Company | `company-dashboard.php`, `company-profile.php`, `post-internship.php`, `company-vacancies.php`, `company-applications.php` |
| Admin | `admin-dashboard.php` (overview/users/vacancies/applications), `admin-companies.php` |

Form processing uses straightforward `*-process.php` files or a small POST section at the top of a management page. Related pages reuse `dashboard.css` and `dashboard.js`. Home and authentication keep their own existing CSS/JS.

## Data and access

- `database.sql` creates the schema and safe additive migrations.
- `db.php` holds the local database connection; `session.php` contains the small shared session/form helpers.
- Passwords use `password_hash()` and `password_verify()`.
- Student and company accounts are separate from administrator accounts.
- CVs and profile photos stay behind authorized PHP endpoints. The upload folders deny direct Apache access.
- Records are closed, archived or made inactive so application history is preserved.
- Administrator setup is local command line only (`setup-admin.php`). No public default admin password is shipped.

## Group update snapshots

The active folder is the source of truth. Numbered full-project snapshots, their ZIPs and commit messages are stored separately under `E:\xammp\htdocs\webproject`. Snapshot names are delivery labels; they do not create Git commits or change Git author identities.

## Completed workflow verification — 9 November 2026

The completed version was exercised in a separate test database using browser automation. Checks covered registration, approval, posting, profile/photo changes, applications with and without CVs, duplicate prevention, applicant review, status tracking, ownership checks, account deactivation, archive/restore, admin reports and pagination. Main layouts were checked at 320, 375, 768 and 1440 pixels. All PHP files passed syntax checks. Existing production accounts and application records were not replaced by test fixtures.

Numbered update folders each contain a complete project snapshot and `COMMIT.txt`. Keep using the active project folder for development. The update folder index lists three updates per confirmed member, with the final snapshot assigned to vidura.

Final Apache verification also checked public assets, authenticated redirects, direct upload/configuration denial and a single consistent set of security response headers.
