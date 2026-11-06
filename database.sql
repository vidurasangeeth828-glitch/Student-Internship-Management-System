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
