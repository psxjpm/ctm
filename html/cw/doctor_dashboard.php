<?php
require_once 'config/db.inc.php';
require_once 'includes/auth_check.php';

$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - QMC</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- 仪表板头部 -->
        <div class="dashboard-header">
            <h1>Doctor Dashboard</h1>
        </div>
        
        <!-- 仪表板网格布局 -->
        <div class="dashboard-grid">
            <!-- 快速搜索 -->
            <div class="card">
                <h2>Quick Search</h2>
                <form method="GET" action="search_patient.php" class="quick-search-form">
                    <input type="text" name="search" placeholder="Patient name or NHS number">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            
            <!-- 快速链接 -->
            <div class="card">
                <h2>Quick Actions</h2>
                <div class="action-links">
                    <a href="/cw/add_test.php" class="action-link">Add New Test</a>
                    <a href="/cw/request_parking.php" class="action-link">Parking Permit</a>
                    <a href="/cw/update_profile.php" class="action-link">Update Profile</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>