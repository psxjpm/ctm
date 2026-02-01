-- QMC Hospital Database - Enhanced with Parking Permit System
CREATE DATABASE IF NOT EXISTS cw_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cw_database;

-- 基础表结构（源自原始ERD）
CREATE TABLE DEPARTMENT (
    Department_id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Location VARCHAR(200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE WARD (
    Ward_id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Location VARCHAR(200),
    Department_id INT,
    Phone VARCHAR(20),
    FOREIGN KEY (Department_id) REFERENCES DEPARTMENT(Department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE DOCTOR (
    Doctor_id INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Specialisation VARCHAR(100),
    Qualification VARCHAR(100),
    Pay DECIMAL(10,2),
    Gender ENUM('Male', 'Female', 'Other'),
    Address_city VARCHAR(100),
    Address_street VARCHAR(200),
    Address_code VARCHAR(20),
    Ward_id INT,
    Staff_no VARCHAR(20) UNIQUE,
    FOREIGN KEY (Ward_id) REFERENCES WARD(Ward_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PATIENT (
    Patient_id VARCHAR(20) PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    PrimaryPhone VARCHAR(20),
    EmergencyPhone VARCHAR(20),
    Gender ENUM('Male', 'Female', 'Other'),
    Address_city VARCHAR(100),
    Address_street VARCHAR(200),
    Address_code VARCHAR(20),
    Date_of_birth DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PATIENT_ADMISSION (
    Admission_id INT PRIMARY KEY AUTO_INCREMENT,
    Ward_id INT NOT NULL,
    Patient_id VARCHAR(20) NOT NULL,
    Doctor_id INT NOT NULL,
    Date DATE NOT NULL,
    Time TIME NOT NULL,
    Room_No VARCHAR(10),
    Bed_No VARCHAR(10),
    Status ENUM('admitted', 'discharged') DEFAULT 'admitted',
    Discharge_date DATE,
    FOREIGN KEY (Ward_id) REFERENCES WARD(Ward_id),
    FOREIGN KEY (Patient_id) REFERENCES PATIENT(Patient_id),
    FOREIGN KEY (Doctor_id) REFERENCES DOCTOR(Doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE TEST (
    Test_id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Category VARCHAR(100),
    Description TEXT,
    Result TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE PATIENT_TEST (
    Test_id INT,
    Patient_id VARCHAR(20),
    Test_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    Result TEXT,
    Prescribed_by INT,
    PRIMARY KEY (Test_id, Patient_id, Test_time),
    FOREIGN KEY (Test_id) REFERENCES TEST(Test_id),
    FOREIGN KEY (Patient_id) REFERENCES PATIENT(Patient_id),
    FOREIGN KEY (Prescribed_by) REFERENCES DOCTOR(Doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 扩展表（项目新增功能）
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    user_type ENUM('doctor', 'admin') NOT NULL,
    doctor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES DOCTOR(Doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE parking_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    car_registration VARCHAR(20) NOT NULL,
    permit_type ENUM('monthly', 'yearly') NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    permit_number VARCHAR(50),
    permit_start_date DATE,
    permit_end_date DATE,
    processed_by INT,
    processed_date TIMESTAMP NULL,
    rejection_reason TEXT,  -- 新增：拒绝原因字段
    FOREIGN KEY (doctor_id) REFERENCES DOCTOR(Doctor_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(64) NOT NULL,
    record_id VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建索引以提高性能
CREATE INDEX idx_parking_doctor_status ON parking_requests(doctor_id, status);
CREATE INDEX idx_parking_end_date ON parking_requests(permit_end_date);
CREATE INDEX idx_patient_name ON PATIENT(Name);
CREATE INDEX idx_parking_status ON parking_requests(status);

-- 插入用户账户（明文密码，按作业要求）
INSERT INTO users (username, password, user_type, doctor_id) VALUES
('mceards', 'lord456', 'doctor', 1),
('moorland', 'buzz48', 'doctor', 2),
('jelina', 'iron99', 'admin', NULL);