# SIMS setup and operation

## Requirements

- XAMPP with Apache, PHP 8.0 or later and MariaDB 10.4 or later.
- PHP extensions: mysqli, mbstring and fileinfo (included in the tested XAMPP installation).
- HTML, CSS, JavaScript, PHP and SQL only; no framework or package installation.

## Install

1. Start Apache and MySQL in XAMPP.
2. Keep the project in `E:\xammp\htdocs\webproject\Student-Internship-Management-System`.
3. Open phpMyAdmin and import `database.sql`. It creates the `sims` database and all tables. Its `CREATE TABLE IF NOT EXISTS` and `ADD COLUMN IF NOT EXISTS` statements can also update an existing installation without removing records.
4. Check `db.php`. The local defaults are host `127.0.0.1`, port `3306`, username `root`, an empty database password and database `sims`. Update those values for another installation.
5. Open `http://localhost/webproject/Student-Internship-Management-System/`.
6. Keep `.htaccess` files in the project root and upload folders. Use Apache for normal operation; PHP's built-in test server does not apply Apache access rules.
7. PHP must be able to write to `uploads/cvs` and `uploads/profile-photos`. Uploaded files use generated filenames and are served through authorized PHP endpoints.

ZIP copies contain source files, assets, upload folders and the SQL schema. A running MySQL database is separate from the ZIP. Existing database records are preserved when updating the main folder; export the database separately through phpMyAdmin if moving real records to another machine.

## Create the first administrator

Existing administrator credentials continue to work. No public admin registration or shared default password is included.

From a local terminal in the project directory, run:

```powershell
& "E:\xammp\php\php.exe" setup-admin.php
```

Enter the administrator's full name, email, password and confirmation. Password input is visible in that local terminal; only a password hash is saved in MySQL. Use a password of 8 to 72 bytes. This command creates a new administrator and never resets an existing account. `setup-admin.php` refuses browser requests.

Use the Home page's Admin link to open `admin-signin.php`. Student/company accounts cannot sign in as administrators.

## Student workflow

1. Register as Student, then sign in.
2. Browse internships using keyword, location and category filters.
3. Open a vacancy and submit one application. A PDF CV up to 2 MB and a cover letter up to 2,000 characters are optional.
4. Use My Applications to follow Submitted, Reviewed, Shortlisted, Accepted and Rejected statuses.
5. Use Profile to update name, contact number, field of interest, education status, introduction, skills, LinkedIn/GitHub links and an optional JPG/PNG photo up to 2 MB. Course and year of study are not requested.

## Company workflow

1. Register as Company. New companies remain Pending until an administrator reviews them.
2. Use Company Profile to add website, description, contact person and registration information. Changing the company name or registration number requires another approval review. Rejected companies can update details and request review again.
3. Approved, active companies can post internships. Only Open, unexpired vacancies from approved active companies are public.
4. Use My Vacancies to search, edit, close and reopen owned vacancies. Reopening requires a current deadline and approval. Closing keeps existing applications.
5. Use Applications to filter by vacancy/status, read applicant details, download attached CVs and update application statuses.

## Administrator workflow

- Dashboard: platform counts, application status overview, recent registrations and an aggregate CSV report.
- Manage users: search accounts and activate/deactivate them. Existing profiles and application history are retained.
- Company Approvals: review details and approve/reject company registrations manually. A business registration number does not automatically prove identity.
- Manage vacancies: search/filter, close, archive, restore as closed and reopen eligible vacancies. Archived vacancies are hidden from the public and cannot be edited/reopened by their company until an administrator restores them.
- Applications: filter all applications, review applicant details/CVs and update permitted statuses. Older forms cannot silently overwrite a newer status change.

## Verification checklist

- Register student/company accounts; verify duplicate email and invalid password feedback.
- Confirm student/company/admin pages reject the wrong role and unauthenticated access.
- Approve a company, post a vacancy, apply as a student and update the status as the company; confirm My Applications shows the change.
- Repeat application submission; confirm it does not create a duplicate.
- Submit with no CV, a valid PDF and an invalid file; confirm correct handling.
- Check that another company/student cannot access someone else's CV or edit a vacancy.
- Deactivate an account; confirm protected requests and sign-in stop working.
- Close/archive a vacancy; confirm the public listing disappears while application history remains.
- Verify filters, admin pagination and summary report.
- Check layouts at 320, 375, 768 and 1440 pixels and use the keyboard/mobile sidebar.

Email/SMS services, payments, interviews and AI candidate scoring are outside this version's scope. There are no seeded production/demo accounts.


## Completed delivery

- Active development folder: `E:\xammp\htdocs\webproject\Student-Internship-Management-System`.
- Point-in-time copies: `E:\xammp\htdocs\webproject\SIMS-Updates`.
- The final snapshot is `vidura-24-final-apache-verification`.
- Read `UPDATE-GUIDE.md` in SIMS-Updates for the member/update list and commit messages. Use snapshots in number order when reviewing the project's progression. Copy project files into your own checkout; keep that checkout's existing `.git` directory.
- Snapshot folders are backups for local file access and are blocked from web access by their parent `.htaccess`.
