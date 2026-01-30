<?php
session_start();

// 检查医生是否已登录
if (!isset($_SESSION["doctor"])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_name = $_SESSION["doctor"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Medical Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-change-password {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-change-password:hover {
            background: #667eea;
            color: white;
        }

        .btn-logout {
            background: #dc3545;
            color: white;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            animation: fadeIn 0.8s ease-out;
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
            border-radius: 15px;
            padding: 40px 30px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card:hover::before {
            opacity: 1;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .card-icon {
            font-size: 64px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .card h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #333;
            position: relative;
            z-index: 1;
        }

        .card p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        /* 特殊样式 - Patient Search 卡片 */
        .card-patient-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .card-patient-search h3,
        .card-patient-search p {
            color: white;
        }

        .card-patient-search:hover {
            transform: translateY(-10px) scale(1.02);
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

            .btn {
                width: 100%;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .header-left h1 {
                font-size: 24px;
            }

            .card {
                padding: 30px 20px;
            }

            .card-icon {
                font-size: 48px;
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
            <h1>Welcome back, Dr. <?php echo htmlspecialchars(ucfirst($doctor_name)); ?>! 👋</h1>
            <p>Staff ID: <?php echo htmlspecialchars($doctor_name); ?></p>
        </div>
        <div class="header-right">
            <div class="user-icon">👨‍⚕️</div>
            <a href="change_password.php" class="btn btn-change-password">🔑 Change Password</a>
            <a href="doctor_logout.php" class="btn btn-logout">🚪 Logout</a>
        </div>
    </div>

    <!-- Dashboard Grid - 只保留3个功能 -->
    <div class="dashboard-grid">
        <!-- Patient Search -->
        <a href="patient_search.php" class="card card-patient-search">
            <div class="card-icon">🔍</div>
            <h3>Patient Search</h3>
            <p>Search and view patient information</p>
        </a>

        <!-- My Patients / Order Tests -->
        <a href="my_patients.php" class="card">
            <div class="card-icon">🩺</div>
            <h3>Order Tests</h3>
            <p>Add patients and order medical tests</p>
        </a>

        <!-- Parking Permit -->
        <a href="parking_permit.php" class="card">
            <div class="card-icon">🅿️</div>
            <h3>Parking Permit</h3>
            <p>Apply for or check parking permits</p>
        </a>
    </div>
</div>

</body>
</html>