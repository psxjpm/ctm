/* QMC Hospital Management System */

DROP DATABASE IF EXISTS qmc_db;
CREATE DATABASE qmc_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qmc_db;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/* 1. User Accounts (Doctors + Admins) */

CREATE TABLE UserAccount (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    role ENUM('doctor', 'admin') NOT NULL
);

INSERT INTO UserAccount (username, password, role) VALUES
('mceards', 'lord456', 'doctor'),
('moorland', 'buzz48', 'doctor'),
('jelina', 'iron99', 'admin');   -- required by coursework

/* 2. Doctor Profile */

CREATE TABLE Doctor (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,  -- 1 doctor corresponds to 1 user account
    staff_no VARCHAR(20) UNIQUE NOT NULL,   -- NHS number
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    specialisation VARCHAR(100),
    qualification VARCHAR(100),
    pay DECIMAL(10,2),
    gender VARCHAR(10),
    address_city VARCHAR(100),
    address_street VARCHAR(100),
    address_code VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES UserAccount(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

INSERT INTO Doctor (user_id, staff_no, first_name, last_name, specialisation, qualification, pay, gender, address_city, address_street, address_code)
VALUES
(1, 'NHS001', 'Mark', 'Ceards', 'Cardiology', 'MBBS', 75000, 'Male', 'Nottingham', 'Main St 20', 'NG1 5AA'),
(2, 'NHS002', 'Sarah', 'Moorland', 'Neurology', 'PhD', 82000, 'Female', 'Nottingham', 'Hill Road 10', 'NG2 4BB');

/* 3. Departments */

CREATE TABLE Department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100)
);

INSERT INTO Department (name, location) VALUES
('Dermatology', 'Block A'),
('Urology', 'Block B'),
('Orthopaedics', 'Block C');

/* 4. Wards */

CREATE TABLE Ward (
    ward_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(100),
    total_patients INT DEFAULT 0,
    FOREIGN KEY (department_id) REFERENCES Department(department_id)
        ON DELETE CASCADE
);

INSERT INTO Ward (department_id, name, phone, location, total_patients) VALUES
(1, 'Derm-W1', '0115-100100', '1st Floor', 0),
(2, 'Uro-W1', '0115-200200', '2nd Floor', 0),
(3, 'Ortho-W1', '0115-300300', '3rd Floor', 0);

/* 5. Patients */

CREATE TABLE Patient (
    patient_id VARCHAR(20) PRIMARY KEY,  -- NHS number
    name VARCHAR(100) NOT NULL,
    primary_phone VARCHAR(20),
    emergency_phone VARCHAR(20),
    gender VARCHAR(10),
    address_city VARCHAR(100),
    address_street VARCHAR(100),
    address_code VARCHAR(20)
);

INSERT INTO Patient (patient_id, name, primary_phone, emergency_phone, gender, address_city, address_street, address_code)
VALUES
('P001', 'John Smith', '07111111111', '07999999999', 'Male', 'Nottingham', 'Forest Rd 5', 'NG3 1AA'),
('P002', 'Emily White', '07222222222', '07888888888', 'Female', 'Nottingham', 'City St 9', 'NG7 2BB');

/* 6. Patient Admissions */

CREATE TABLE Patient_Admission (
    admission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(20) NOT NULL,
    ward_id INT NOT NULL,
    doctor_id INT NOT NULL,
    admission_date DATE NOT NULL,
    admission_time TIME NOT NULL,
    room_no VARCHAR(10),
    bed_no VARCHAR(10),
    status ENUM('admitted','discharged') DEFAULT 'admitted',
    FOREIGN KEY (patient_id) REFERENCES Patient(patient_id)
        ON DELETE CASCADE,
    FOREIGN KEY (ward_id) REFERENCES Ward(ward_id)
        ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES Doctor(doctor_id)
        ON DELETE CASCADE
);

INSERT INTO Patient_Admission (patient_id, ward_id, doctor_id, admission_date, admission_time, room_no, bed_no, status)
VALUES
('P001', 1, 1, '2025-01-10', '10:30:00', '101', '1A', 'admitted'),
('P002', 2, 2, '2025-01-11', '12:00:00', '202', '2B', 'admitted');

/* 7. Test Types */

CREATE TABLE Test (
    test_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    description VARCHAR(255)
);

INSERT INTO Test (name, category, description) VALUES
('Blood Test', 'Blood', 'General blood analysis'),
('MRI Scan', 'MRI', 'Full body MRI scan');

/* 8. Patient Test Records */

CREATE TABLE Patient_Test (
    pt_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(20) NOT NULL,
    test_id INT NOT NULL,
    doctor_id INT NOT NULL,
    test_date DATE,
    test_time TIME,
    result VARCHAR(255),
    FOREIGN KEY (patient_id) REFERENCES Patient(patient_id)
        ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES Test(test_id)
        ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES Doctor(doctor_id)
        ON DELETE CASCADE
);

INSERT INTO Patient_Test (patient_id, test_id, doctor_id, test_date, test_time, result)
VALUES
('P001', 1, 1, '2025-01-12', '09:00:00', 'Normal'),
('P002', 2, 2, '2025-01-15', '14:30:00', 'Pending');

/* 9. Parking Permit System */

CREATE TABLE Parking_Permit (
    permit_id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    car_registration VARCHAR(20),
    permit_choice ENUM('monthly','yearly') NOT NULL,
    activation_date DATE,
    end_date DATE,
    amount DECIMAL(10,2),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_reason VARCHAR(255),
    permit_number VARCHAR(30),
    FOREIGN KEY (doctor_id) REFERENCES Doctor(doctor_id)
        ON DELETE CASCADE
);

/* Sample pending request */
INSERT INTO Parking_Permit (doctor_id, car_registration, permit_choice, amount, status)
VALUES
(1, 'ABC1234', 'monthly', 50.00, 'pending');

/* 10. Audit Log */

CREATE TABLE Audit_Log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('select','insert','update','delete') NOT NULL,
    table_name VARCHAR(50),
    record_id VARCHAR(50),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (user_id) REFERENCES UserAccount(user_id)
        ON DELETE CASCADE
);

/* Example log */
INSERT INTO Audit_Log (user_id, action_type, table_name, record_id, details)
VALUES
(1, 'select', 'Patient', 'P001', 'Doctor viewed patient record');

