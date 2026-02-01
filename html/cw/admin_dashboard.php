<?php
require_once 'config/db.inc.php';
require_once 'includes/auth_check.php';

// 确保只有管理员可以访问
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: /cw/doctor_dashboard.php');
    exit;
}

$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QMC</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- 仪表板头部 -->
        <div class="dashboard-header admin">
            <h1>Administrator Dashboard</h1>
        </div>
        
        <!-- 统计卡片网格 -->
        <div class="dashboard-grid">
            <!-- 医生总数 -->
            <div class="card">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM DOCTOR");
                $doctorCount = $stmt->fetchColumn();
                ?>
                <h1><?php echo $doctorCount; ?></h1>
                <p>Total Doctors</p>
            </div>
            
            <!-- 患者总数 -->
            <div class="card">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM PATIENT");
                $patientCount = $stmt->fetchColumn();
                ?>
                <h1><?php echo $patientCount; ?></h1>
                <p>Total Patients</p>
            </div>
            
            <!-- 待处理停车请求 -->
            <div class="card">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM parking_requests WHERE status = 'pending'");
                $pendingParking = $stmt->fetchColumn();
                ?>
                <h1><?php echo $pendingParking; ?></h1>
                <p>Pending Parking Requests</p>
            </div>
        </div>
        
        <!-- 功能卡片网格 -->
        <div class="dashboard-grid">
            <!-- 用户管理 -->
            <div class="card">
                <h2>User Management</h2>
                <p>Create and manage system users</p>
                <div class="action-buttons">
                    <a href="/cw/admin/create_doctor.php" class="btn btn-primary btn-medium">Create New Doctor</a>
                </div>
            </div>
            
            <!-- 停车管理 -->
            <div class="card">
                <h2>Parking Management</h2>
                <p>Approve or reject parking permit requests</p>
                <div class="action-buttons">
                    <a href="/cw/admin/approve_parking.php" class="btn btn-success btn-medium">Review Requests</a>
                </div>
            </div>
            
            <!-- 安全与审计 -->
            <div class="card">
                <h2>Security & Audit</h2>
                <p>Monitor system activities and access</p>
                <div class="action-buttons">
                    <a href="/cw/admin/audit_trail.php" class="btn btn-warning btn-medium">View Audit Trail</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>