<?php
// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 如果用户未登录，重定向到登录页面
if (!isset($_SESSION['user_id'])) {
    header('Location: /cw/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QMC Hospital System</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <!-- 网站头部 -->
    <header>
        <div class="header-content">
            <!-- 网站Logo -->
            <div class="logo">
                QMC Hospital System
            </div>
            <!-- 用户信息 -->
            <div class="user-info">
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="welcome-text">
                        Welcome, 
                        <?php if ($_SESSION['user_type'] === 'doctor'): ?>
                            Dr. <?php echo htmlspecialchars($_SESSION['doctor_name'] ?? $_SESSION['username']); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)
                        <?php endif; ?>
                    </span>
                    <a href="/cw/logout.php" class="btn btn-danger btn-small">Logout</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- 导航菜单 -->
        <nav>
            <ul>
                <?php if ($_SESSION['user_type'] === 'doctor'): ?>
                    <!-- 医生菜单 -->
                    <li><a href="/cw/doctor_dashboard.php">Dashboard</a></li>
                    <li><a href="/cw/search_patient.php">Search Patients</a></li>
                    <li><a href="/cw/add_test.php">Add Test</a></li>
                    <li><a href="/cw/request_parking.php">Parking Permit</a></li>
                    <li><a href="/cw/update_profile.php">Profile</a></li>
                <?php else: ?>
                    <!-- 管理员菜单 -->
                    <li><a href="/cw/admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="/cw/search_patient.php">Search Patients</a></li>
                    <li><a href="/cw/add_test.php">Add Test</a></li>
                    <li><a href="/cw/admin/create_doctor.php">Create Doctor</a></li>
                    <li><a href="/cw/admin/approve_parking.php">Approve Parking</a></li>
                    <li><a href="/cw/admin/audit_trail.php">Audit Trail</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <!-- 主内容区域开始 -->
    <main>