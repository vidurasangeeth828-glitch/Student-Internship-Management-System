# SIMS authentication

1. Start Apache and MySQL in XAMPP.
2. Open http://localhost/phpmyadmin/ and import database.sql once. It creates the sims database with users, students and companies tables.
3. Open http://localhost/sims/ and click Sign In / Sign Up.
4. Create a Student or Company account, then sign in.

If MySQL uses a custom username, password or port, change db.php.

Students register with Full name, Email, Password and Confirm password. Course / Field and Year of study are reserved for the later Profile page.

Companies register with Company name, Email, Contact number, optional Business registration number, Password and Confirm password. New companies are Pending. Their numbers are stored for later admin review, entering a registration number does not automatically verify a company.

Remember email saves the sign-in email in a 30-day cookie. It does not store the password.

After login, account.php is a protected account confirmation page with the role, company approval state and Sign out. Role dashboards and the admin approval interface belong to the next implementation stage.

There are no seeded demo accounts. Register your own account using the form.
