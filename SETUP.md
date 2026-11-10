# SIMS authentication

1. Start Apache and MySQL in XAMPP.
2. Open http://localhost/phpmyadmin/ and import database.sql once. It creates the sims database with users, students and companies tables.
3. Open http://localhost/sims/ and click Sign In / Sign Up.
2. Open http://localhost/phpmyadmin/ and import database.sql once. It creates the sims database with users, students, companies, internships and applications tables.
3. Open http://localhost/webproject/Student-Internship-Management-System/ and click Sign In / Sign Up.
4. Create a Student or Company account, then sign in.

If MySQL uses a custom username, password or port, change db.php.

Students register with Full name, Email, Password and Confirm password. Course / Field and Year of study are reserved for the later Profile page.

Companies register with Company name, Email, Contact number, optional Business registration number, Password and Confirm password. New companies are Pending. Their numbers are stored for later admin review, entering a registration number does not automatically verify a company.

Remember email saves the sign-in email in a 30-day cookie. It does not store the password.

After login, account.php is a protected account confirmation page with the role, company approval state and Sign out. Role dashboards and the admin approval interface belong to the next implementation stage.

Students reach student-dashboard.php after login. The dashboard uses dashboard.css for the glass cards, grid and colored glows, and dashboard.js for the mobile sidebar.

Students reach student-dashboard.php after login. The dashboard uses dashboard.css for the solid cards, grid and colored glows, and dashboard.js for the mobile sidebar.

The dashboard reads applications and internships from MySQL. Import the updated database.sql to create these tables on an existing installation. It uses CREATE TABLE IF NOT EXISTS and preserves existing users.

Browse Internships and the Home/Dashboard search forms open internships.php. Keyword, location and category filters work together. This public page shows open, unexpired vacancies from approved active companies.

Import the updated database.sql for the category column. Existing vacancies receive Other until their category is set. View details currently expands the description on the card. A separate details/application flow will be built next.

My Applications currently jumps to the dashboard applications section. The separate application tracker and editable profile pages will be built in the next stage.

Profile and My account open account.php, which includes a link back to the Student Dashboard. Companies continue to use account.php until the Company Dashboard is built.
My Applications opens my-applications.php. Students can view their applications and download their own CV through download-cv.php. Student profiles open student-profile.php; student-profile-process.php saves the form using the shared dashboard.css styles.

Student profiles include field of interest, education status, contact number, about me, skills, and optional LinkedIn/GitHub links. The sign-in email is read-only. Course and year of study are not shown. For an existing database, re-import the updated database.sql to add the profile columns safely. Existing records are retained.

Profile and My account open account.php, which includes a link back to the Student Dashboard. Companies continue to use account.php until the Company Dashboard is built.

There are no seeded demo accounts. Register your own account using the form.
