# Student Internship Management System

A web-based project for managing student internships. It provides separate areas for students, companies, and administrators to manage internship-related activities in one place.

## Main Features

### Students
- Register and sign in to their accounts.
- Manage student profiles and CVs.
- Browse available internships and view internship details.
- Apply for internships and view submitted applications.

### Companies
- Sign in and manage company information.
- Post and manage internship vacancies.
- View internship applications.

### Administrators
- Access the administration area.
- Manage company-related information and system records.

*Features available may depend on the version of the project you clone.*

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local server:** XAMPP (Apache and MySQL)
- **Code editor:** Visual Studio Code (optional)

## Requirements

Install the following before running the project:

1. [XAMPP](https://www.apachefriends.org/), with PHP 8 or later and MySQL/MariaDB
2. [Git](https://git-scm.com/) (to clone the repository)
3. A web browser

## Installation and Setup

### 1. Clone the repository

Open **PowerShell**, **Command Prompt**, or the **VS Code terminal**, then run:

```bash
git clone https://github.com/vidurasangeeth828-glitch/Student-Internship-Management-System.git
```

Alternatively, open the GitHub repository, select **Code > Download ZIP**, and extract it.

### 2. Place the project inside `htdocs`

Move the extracted or cloned `Student-Internship-Management-System` folder into your XAMPP `htdocs` directory. A typical Windows location is:

```text
C:\xampp\htdocs\Student-Internship-Management-System\
```

If you cloned it directly inside `htdocs`, there is no need to move it again.

### 3. Start XAMPP

Open the **XAMPP Control Panel** and start:

- **Apache**
- **MySQL**

### 4. Set up the database

1. Open `http://localhost/phpmyadmin/`.
2. Open the project's `database.sql` file and check the database name it expects.
3. Create that database in phpMyAdmin if needed.
4. Select the database and use **Import** to import `database.sql`.
5. Open `db.php` and make sure its database name, host, username, and password match your local XAMPP configuration.

**Important:** The exact database name and login credentials are determined by the project's SQL and PHP configuration. Do not assume that the default XAMPP credentials are appropriate for every installation.

### 5. Run the website

Open a browser and visit:

```text
http://localhost/Student-Internship-Management-System/
```

If the home page does not load, try:

```text
http://localhost/Student-Internship-Management-System/index.php
```

## Using the System

1. Open the website in your browser.
2. Create an account or sign in using a valid account already in the database.
3. Use the appropriate student, company, or admin pages.
4. Students can explore internship opportunities and manage applications; companies can manage vacancies and review applications.

## Important Project Files

| File or folder | Purpose |
| --- | --- |
| `index.php` | Main website entry point |
| `signin.php` | Sign-in page |
| `signin-process.php` | Processes sign-in requests |
| `signup.php` | Registration page |
| `student-dashboard.php` | Student dashboard |
| `student-profile.php` | Student profile page |
| `internships.php` | Internship listings |
| `apply.php` | Internship application page |
| `company-dashboard.php` | Company dashboard |
| `database.sql` | Database schema and/or starter data |
| `db.php` | Database connection settings |
| `assets/` | Images and other static assets |

*Some files may differ between branches or project versions.*

## Troubleshooting

**Apache or MySQL does not start:** Check for port conflicts in XAMPP and restart the service.

**Database connection error:** Confirm that MySQL is running, the database was imported, and `db.php` has the correct connection details.

**Page not found (404):** Confirm the folder name and that the project is inside the active XAMPP `htdocs` directory.

**Cookie path error:** If PHP reports an invalid `setcookie()` path, use a valid URL cookie path such as `/` rather than a Windows filesystem path. Avoid unencoded spaces in URL paths.

**Login does not work:** Check that the database contains a valid active user, and verify the login form and database configuration. Do not publish passwords or private user records.

## Privacy and Security

Do not commit real CVs, uploaded profile photos, passwords, private user data, or local secret configuration files to a public repository. Use test data for demonstrations.

## Repository

[Student Internship Management System on GitHub](https://github.com/vidurasangeeth828-glitch/Student-Internship-Management-System)

---

**Note:** This README describes the installation and core functionality based on the project information shared. Check the final `database.sql`, `db.php`, and available pages against the version you publish.# Student-Internship-Management-System
