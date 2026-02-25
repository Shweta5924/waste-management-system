-- Database for Waste Segregation Monitoring System (Optimized)

DROP DATABASE IF EXISTS waste_db; 
CREATE DATABASE waste_db;
USE waste_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'supervisor', 'staff', 'citizen') NOT NULL DEFAULT 'citizen',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Wards Table
CREATE TABLE wards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_number VARCHAR(50) NOT NULL UNIQUE,
    area_name VARCHAR(100) NOT NULL,
    population INT NOT NULL,
    supervisor_id INT,
    FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Waste Entries Table (Raw Data Only)
CREATE TABLE waste_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ward_id INT NOT NULL,
    date DATE NOT NULL,
    wet_waste DECIMAL(10, 2) NOT NULL DEFAULT 0,
    dry_waste DECIMAL(10, 2) NOT NULL DEFAULT 0,
    mixed_waste DECIMAL(10, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ward_date (ward_id, date), -- 2️⃣ Prevent duplicate daily entries
    INDEX idx_ward (ward_id), -- 3️⃣ Performance Index
    INDEX idx_date (date)     -- 3️⃣ Performance Index
);

-- Vehicles Table (5️⃣ Future Scope)
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(50) NOT NULL UNIQUE,
    driver_name VARCHAR(100),
    driver_phone VARCHAR(20),
    ward_id INT, -- Main assigned ward
    status ENUM('active', 'maintenance') DEFAULT 'active',
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE SET NULL
);

-- Complaints Table
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    citizen_name VARCHAR(100) NOT NULL,
    ward_id INT NOT NULL,
    complaint_text TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    resolution_image_path VARCHAR(255) NULL,
    status ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 1️⃣ View for Analytics (Calculates Total, Percentage, Grade dynamically)
CREATE VIEW waste_analytics AS
SELECT 
    we.id,
    we.ward_id,
    w.ward_number,
    w.area_name,
    we.date,
    we.wet_waste,
    we.dry_waste,
    we.mixed_waste,
    (we.wet_waste + we.dry_waste + we.mixed_waste) AS total_waste,
    CASE 
        WHEN (we.wet_waste + we.dry_waste + we.mixed_waste) = 0 THEN 0
        ELSE ((we.wet_waste + we.dry_waste) / (we.wet_waste + we.dry_waste + we.mixed_waste)) * 100 
    END AS segregation_percentage,
    CASE
        WHEN (we.wet_waste + we.dry_waste + we.mixed_waste) = 0 THEN 'D'
        WHEN ((we.wet_waste + we.dry_waste) / (we.wet_waste + we.dry_waste + we.mixed_waste)) * 100 >= 90 THEN 'A'
        WHEN ((we.wet_waste + we.dry_waste) / (we.wet_waste + we.dry_waste + we.mixed_waste)) * 100 >= 75 THEN 'B'
        WHEN ((we.wet_waste + we.dry_waste) / (we.wet_waste + we.dry_waste + we.mixed_waste)) * 100 >= 60 THEN 'C'
        ELSE 'D'
    END AS grade,
    we.created_at
FROM waste_entries we
JOIN wards w ON we.ward_id = w.id;

-- Seed Data
INSERT INTO users (name, email, password, role, phone) VALUES 
('System Admin', 'admin@waste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '9876543210'),
('Supervisor One', 'super1@waste.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', '9876543211');

INSERT INTO wards (ward_number, area_name, population, supervisor_id) VALUES 
('W-001', 'Downtown', 5000, 2),
('W-002', 'Uptown', 3500, 2);

-- Insert Dummy Vehicle
INSERT INTO vehicles (vehicle_number, driver_name, ward_id) VALUES 
('KA-01-AB-1234', 'Ramesh Driver', 1);
