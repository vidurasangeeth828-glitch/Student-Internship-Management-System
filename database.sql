CREATE DATABASE IF NOT EXISTS sims CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sims;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'company') NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    course_or_field VARCHAR(100) NOT NULL DEFAULT '',
    year_of_study TINYINT UNSIGNED NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    business_registration_no VARCHAR(80) NOT NULL DEFAULT '',
    website VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NULL,
    contact_person VARCHAR(100) NOT NULL DEFAULT '',
    approval_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS internships (
    internship_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'Other',
    location VARCHAR(80) NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('Open', 'Closed', 'Archived') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
) ENGINE=InnoDB;

ALTER TABLE internships ADD COLUMN IF NOT EXISTS category VARCHAR(80) NOT NULL DEFAULT 'Other' AFTER description;


ALTER TABLE internships ADD COLUMN IF NOT EXISTS category VARCHAR(80) NOT NULL DEFAULT 'Other' AFTER description;

CREATE TABLE IF NOT EXISTS applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    internship_id INT NOT NULL,
    cover_letter TEXT NULL,
    cv_path VARCHAR(255) DEFAULT NULL,
    status ENUM('Submitted', 'Reviewed', 'Shortlisted', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Submitted',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, internship_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (internship_id) REFERENCES internships(internship_id)
) ENGINE=InnoDB;



-- Student profile fields (safe to run again).
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS field_of_interest VARCHAR(100) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS about_me TEXT NULL,
    ADD COLUMN IF NOT EXISTS education_status VARCHAR(30) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS skills VARCHAR(500) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS linkedin_url VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS github_url VARCHAR(255) NOT NULL DEFAULT '';

-- Student profile photo.
ALTER TABLE students ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) NOT NULL DEFAULT '';
