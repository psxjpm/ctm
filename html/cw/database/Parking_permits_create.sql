-- 停车许可申请表
-- 添加到你的 hospital 数据库

CREATE TABLE IF NOT EXISTS `parking_permits` (
  `permit_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` varchar(100) NOT NULL,
  `vehicle_make` varchar(100) NOT NULL,
  `vehicle_model` varchar(100) NOT NULL,
  `vehicle_color` varchar(50) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `parking_plan` enum('monthly','yearly') NOT NULL,
  `plan_cost` decimal(10,2) NOT NULL,
  `application_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  PRIMARY KEY (`permit_id`),
  UNIQUE KEY `license_plate` (`license_plate`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `parking_permits_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`staffno`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 插入测试数据
INSERT INTO `parking_permits` (`doctor_id`, `vehicle_make`, `vehicle_model`, `vehicle_color`, `license_plate`, `parking_plan`, `plan_cost`, `status`, `admin_comment`) VALUES
('CH007', 'Toyota', 'Camry', 'Silver', 'AB12 CDE', 'yearly', 600.00, 'approved', 'Approved for yearly parking'),
('QM004', 'Honda', 'Civic', 'Blue', 'XY98 ZWQ', 'monthly', 60.00, 'pending', NULL);