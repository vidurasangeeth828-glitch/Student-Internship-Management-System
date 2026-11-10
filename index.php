 chamodya
=======
 nethmi
 main
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMS | Student Internship Management System</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header class="header">
        <a class="brand" href="index.php" aria-label="SIMS home">
            <img class="brand-icon" src="assets/logo.svg" alt="" width="65" height="57">
            <strong>SIMS</strong>

        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <nav id="main-navigation" aria-label="Main navigation">
            <a href="internships.php">Internships</a>
            <button class="nav-link" type="button" data-section="Companies">Companies</button>
 chamodya
            <a class="admin-button" href="admin-signin.php">Admin</a>
=======
            <button class="admin-button" type="button" data-section="Admin">Admin</button>
            <a class="account-button" href="signin.php">Sign In / Sign Up</a>
        </nav>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <img class="hero-art art-left" src="assets/hero-people.png" alt="" width="1024" height="1024">

            <div class="hero-copy">
                <h1 id="hero-title">Find Your Internship<br><span>Opportunity</span></h1>
                <p>Discover internships, connect with companies,<br> and manage applications in one place.</p>
            </div>

            <img class="hero-art art-right" src="assets/hero-analytics.png" alt="" width="1024" height="1024">
        </section>

        <form class="search-bar" id="opportunity-search" action="internships.php" method="get" role="search">
            <div class="search-inputs">
            <label class="search-field keyword-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg>
                <span class="sr-only">Job title or keyword</span>
                <input name="keyword" placeholder="Job title or keyword" maxlength="100">
            </label>
            <label class="search-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="sr-only">Location</span>
                <input name="location" placeholder="Location" maxlength="80">
            </label>
            <label class="search-field category-field">
                <span class="sr-only">Category</span>
                <select name="category" aria-label="Category">
                    <option value="">Category</option>
                    <option>Software Development</option>
                    <option>Data &amp; Analytics</option>
                    <option>Design &amp; Creative</option>
                    <option>Marketing</option>
                    <option>Business &amp; Management</option>
                    <option>Engineering</option>
                    <option>Finance &amp; Accounting</option>
                    <option>Other</option>
                </select>
            </label>
            </div>
            <button class="search-button" type="submit">Search Opportunities</button>
        </form>

    </main>

    <dialog id="page-dialog" aria-labelledby="dialog-title">
        <h2 id="dialog-title"></h2>
        <p id="dialog-message"></p>
        <form method="dialog"><button class="account-button" type="submit">Close</button></form>
    </dialog>
</body>
</html>
=======
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMS | Student Internship Management System</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header class="header">
        <a class="brand" href="index.php" aria-label="SIMS home">
            <img class="brand-icon" src="assets/logo.svg" alt="" width="65" height="57">
            <strong>SIMS</strong>

        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <nav id="main-navigation" aria-label="Main navigation">
            <a href="internships.php">Internships</a>
            <button class="nav-link" type="button" data-section="Companies">Companies</button>
            <button class="admin-button" type="button" data-section="Admin">Admin</button>
            <a class="account-button" href="signin.php">Sign In / Sign Up</a>
        </nav>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <img class="hero-art art-left" src="assets/hero-people.png" alt="" width="1024" height="1024">

            <div class="hero-copy">
                <h1 id="hero-title">Find Your Internship<br><span>Opportunity</span></h1>
                <p>Discover internships, connect with companies,<br> and manage applications in one place.</p>
            </div>

            <img class="hero-art art-right" src="assets/hero-analytics.png" alt="" width="1024" height="1024">
        </section>

        <form class="search-bar" id="opportunity-search" action="internships.php" method="get" role="search">
            <div class="search-inputs">
            <label class="search-field keyword-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg>
                <span class="sr-only">Job title or keyword</span>
                <input name="keyword" placeholder="Job title or keyword" maxlength="100">
            </label>
            <label class="search-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="sr-only">Location</span>
                <input name="location" placeholder="Location" maxlength="80">
            </label>
            <label class="search-field category-field">
                <span class="sr-only">Category</span>
                <select name="category" aria-label="Category">
                    <option value="">Category</option>
                    <option>Software Development</option>
                    <option>Data &amp; Analytics</option>
                    <option>Design &amp; Creative</option>
                    <option>Marketing</option>
                    <option>Business &amp; Management</option>
                    <option>Engineering</option>
                    <option>Finance &amp; Accounting</option>
                    <option>Other</option>
                </select>
            </label>
            </div>
            <button class="search-button" type="submit">Search Opportunities</button>
        </form>

    </main>

    <dialog id="page-dialog" aria-labelledby="dialog-title">
        <h2 id="dialog-title"></h2>
        <p id="dialog-message"></p>
        <form method="dialog"><button class="account-button" type="submit">Close</button></form>
    </dialog>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMS | Student Internship Management System</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header class="header">
        <a class="brand" href="index.php" aria-label="SIMS home">
            <img class="brand-icon" src="assets/logo.svg" alt="" width="65" height="57">
            <strong>SIMS</strong>

        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Open navigation menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <nav id="main-navigation" aria-label="Main navigation">
            <a href="internships.php">Internships</a>
            <button class="nav-link" type="button" data-section="Companies">Companies</button>
            <button class="admin-button" type="button" data-section="Admin">Admin</button>
            <a class="account-button" href="signin.php">Sign In / Sign Up</a>
        </nav>
    </header>

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <img class="hero-art art-left" src="assets/hero-people.png" alt="" width="1024" height="1024">

            <div class="hero-copy">
                <h1 id="hero-title">Find Your Internship<br><span>Opportunity</span></h1>
                <p>Discover internships, connect with companies,<br> and manage applications in one place.</p>
            </div>

            <img class="hero-art art-right" src="assets/hero-analytics.png" alt="" width="1024" height="1024">
        </section>

        <form class="search-bar" id="opportunity-search" action="internships.php" method="get" role="search">
            <div class="search-inputs">
            <label class="search-field keyword-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg>
                <span class="sr-only">Job title or keyword</span>
                <input name="keyword" placeholder="Job title or keyword" maxlength="100">
            </label>
            <label class="search-field">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="sr-only">Location</span>
                <input name="location" placeholder="Location" maxlength="80">
            </label>
            <label class="search-field category-field">
                <span class="sr-only">Category</span>
                <select name="category" aria-label="Category">
                    <option value="">Category</option>
                    <option>Software Development</option>
                    <option>Data &amp; Analytics</option>
                    <option>Design &amp; Creative</option>
                    <option>Marketing</option>
                    <option>Business &amp; Management</option>
                    <option>Engineering</option>
                    <option>Finance &amp; Accounting</option>
                    <option>Other</option>
                </select>
            </label>
            </div>
            <button class="search-button" type="submit">Search Opportunities</button>
        </form>

    </main>

    <dialog id="page-dialog" aria-labelledby="dialog-title">
        <h2 id="dialog-title"></h2>
        <p id="dialog-message"></p>
        <form method="dialog"><button class="account-button" type="submit">Close</button></form>
    </dialog>
</body>
</html>
