-- Locker Manager Database Schema
-- Run this file to initialize the database:
--   mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS locker_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE locker_manager;

-- School Years
CREATE TABLE IF NOT EXISTS school_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_year (start_year, end_year)
) ENGINE=InnoDB;

-- Houses
CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(20) DEFAULT '#999999',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Classes
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL UNIQUE,
    house_id INT DEFAULT NULL,
    level VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    class_id INT DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Rooms
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL UNIQUE,
    building VARCHAR(20) DEFAULT NULL,
    floor VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Lockers
CREATE TABLE IF NOT EXISTS lockers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    locker_number VARCHAR(50) NOT NULL UNIQUE,
    building VARCHAR(20) DEFAULT NULL,
    status ENUM('available', 'reserve', 'maintenance') NOT NULL DEFAULT 'available',
    room_id INT DEFAULT NULL,
    floor VARCHAR(10) DEFAULT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Locks
CREATE TABLE IF NOT EXISTS locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lock_number VARCHAR(50) NOT NULL UNIQUE,
    combination VARCHAR(50) DEFAULT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Locker Assignments (year-based)
CREATE TABLE IF NOT EXISTS locker_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    locker_id INT NOT NULL,
    lock_id INT DEFAULT NULL,
    school_year_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_locker_year (locker_id, school_year_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (locker_id) REFERENCES lockers(id) ON DELETE CASCADE,
    FOREIGN KEY (lock_id) REFERENCES locks(id) ON DELETE SET NULL,
    FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default school years
INSERT INTO school_years (start_year, end_year, is_active) VALUES
    (2025, 2026, 0),
    (2026, 2027, 1),
    (2027, 2028, 0);

-- Insert default houses
INSERT INTO houses (name, color) VALUES
    ('Ansembourg', '#4FC3F7'),
    ('Hollenfels', '#FFB74D'),
    ('Koerich', '#A5D6A7'),
    ('Simmern', '#FFD54F'),
    ('Schoenfels', '#CE93D8'),
    ('Larochette', '#4DD0E1'),
    ('Mersch', '#FF8A65'),
    ('Pettingen', '#F48FB1');
