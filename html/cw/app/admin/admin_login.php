<?php
session_start();

// 检查管理员是否已登录
if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Medical Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-left h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 8px;
        }

        .header-left p {
            color: #666;
            font-size: 16px;
        }

        .header-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .user-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .btn-logout {
            padding: 12px 24px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .section-title {
            color: white;
            font-size: 28px;
            margin-bottom: 30px;
            margin-top: 20px;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            animation: fadeIn 0.8s ease-out;
            margin-bottom: 40px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 45px 30px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-admin {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .card-doctor {
            background: linear-gradient(135deg, #56ccf2 0%, #2f80ed 100%);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card:hover::before {
            opacity: 1;
        }

        .card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .card-icon {
            font-size: 72px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.2));
        }

        .card h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: white;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .card p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            line-height: 1.6;
            position: relative;
            z-index: 1;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .divider {
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            margin: 40px 0 20px 0;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .header-right {
                flex-direction: column;
                width: 100%;
            }

            .btn-logout {
                width: 100%;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 24px;
            }

            .card {
                padding: 35px 25px;
            }

            .card-icon {
                font-size: 60px;
            }

            .card h3 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1>Welcome back, Administrator! 👋</h1>
            <p><?php echo htmlspecialchars($admin_name); ?></p>
        </div>
        <div class="header-right">
            <div class="user-icon">👨‍💼</div>
            <a href="admin_logout.php" class="btn-logout">🚪 Logout</a>
        </div>
    </div>

    <!-- Admin Functions -->
    <div class="section-title">
        <span>🔧</span>
        <span>Admin Functions</span>
    </div>
    
    <div class="dashboard-grid">
        <a href="add_doctor.php" class="card card-admin">
            <div class="card-icon">➕</div>
            <h3>Add Doctor</h3>
            <p>Register new doctor accounts in the system</p>
        </a>

        <a href="review_parking_permits.php" class="card card-admin">
            <div class="card-icon">📋</div>
            <h3>Review Parking</h3>
            <p>Approve or reject parking permit applications</p>
        </a>

        <a href="audit_log.php" class="card card-admin">
            <div class="card-icon">📊</div>
            <h3>Audit Log</h3>
            <p>View system activity records and compliance reports</p>
        </a>
    </div>

    <!-- Divider -->
    <div class="divider"></div>

    <!-- Doctor Functions (Admin Access) -->
    <div class="section-title">
        <span>👨‍⚕️</span>
        <span>Doctor Functions (Admin Access)</span>
    </div>
    
    <div class="dashboard-grid">
        <a href="../doctor/patient_search.php" class="card card-doctor">
            <div class="card-icon">🔍</div>
            <h3>Patient Search</h3>
            <p>Search and view patient records by ID or name</p>
        </a>

        <a href="../doctor/my_patients.php" class="card card-doctor">
            <div class="card-icon">👥</div>
            <h3>My Patients</h3>
            <p>View complete list of all patients in the system</p>
        </a>

        <a href="../doctor/my_parking.php" class="card card-doctor">
            <div class="card-icon">🅿️</div>
            <h3>Parking Management</h3>
            <p>View and manage parking permit applications</p>
        </a>
    </div>
</div>

</body>
</html>