<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #a8e6cf 0%, #3ecd7c 100%);
            min-height: 100vh;
            padding: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            text-align: center;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            font-size: 45px;
            margin-bottom: 12px;
            color: #3ecd7c;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #a8e6cf 0%, #3ecd7c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #666;
            font-size: 15px;
            margin-bottom: 8px;
        }

        .emergency {
            background: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }

        .section-title {
            color: white;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 25px;
            animation: fadeIn 0.8s ease-out;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
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
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
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
            background: linear-gradient(135deg, rgba(168, 230, 207, 0.1) 0%, rgba(62, 205, 124, 0.1) 100%);
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
            font-size: 70px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .card-admin .card-icon {
            background: linear-gradient(135deg, #a8e6cf 0%, #3ecd7c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-doctor .card-icon {
            color: #26a69a;
        }

        .card h3 {
            font-size: 26px;
            margin-bottom: 12px;
            color: #333;
            position: relative;
            z-index: 1;
        }

        .card p {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
            border: none;
            cursor: pointer;
        }

        .btn-admin {
            background: linear-gradient(135deg, #a8e6cf 0%, #3ecd7c 100%);
            color: white;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(62, 205, 124, 0.4);
        }

        .btn-doctor {
            background: #26a69a;
            color: white;
        }

        .btn-doctor:hover {
            background: #00897b;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(38, 166, 154, 0.4);
        }

        .info-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            margin-bottom: 25px;
        }

        .info-section h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(135deg, #a8e6cf 0%, #3ecd7c 100%);
            border-image-slice: 1;
        }

        .info-section ul {
            margin-left: 20px;
            margin-top: 12px;
            line-height: 1.8;
            color: #666;
        }

        .info-section ul li {
            margin-bottom: 8px;
            font-size: 15px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            color: #666;
            margin-top: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .footer p {
            margin: 3px 0;
            font-size: 14px;
        }

        .footer .heart {
            color: #3ecd7c;
            animation: heartbeat 1.5s infinite;
        }

        @keyframes heartbeat {
            0%, 100% {
                transform: scale(1);
            }
            25% {
                transform: scale(1.2);
            }
            50% {
                transform: scale(1);
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 26px;
            }

            .logo {
                font-size: 36px;
            }

            .card-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .section-title {
                font-size: 22px;
            }

            .card {
                padding: 30px 20px;
            }

            .card-icon {
                font-size: 56px;
            }

            .card h3 {
                font-size: 20px;
            }

            .info-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="logo"><i class="fas fa-hospital"></i></div>
        <h1>Hospital Management System</h1>
        <p>Providing Quality Healthcare Services</p>
        <p style="color: #3ecd7c; font-weight: 600; margin-top: 5px;"><i class="fas fa-phone"></i> +1 (123) 456-7890</p>
    </div>

    <!-- Emergency Banner -->
    <div class="emergency">
        <i class="fas fa-ambulance"></i> EMERGENCY: CALL 911 OR (123) 456-7890 <i class="fas fa-ambulance"></i>
    </div>

    <!-- Login Cards -->
    <div class="section-title"><i class="fas fa-lock"></i> System Access</div>
    <div class="card-container">
        <div class="card card-admin">
            <div class="card-icon">
                <i class="fas fa-user-nurse"></i>
            </div>
            <h3>Admin Login</h3>
            <p>Access administrative panel for system management</p>
            <a href="admin/admin_login.php" class="btn btn-admin">Admin Login</a>
        </div>

        <div class="card card-doctor">
            <div class="card-icon">
                <i class="fas fa-user-md"></i>
            </div>
            <h3>Doctor Login</h3>
            <p>For doctors to access patient records and appointments</p>
            <a href="doctor/doctor_login.php" class="btn btn-doctor">Doctor Login</a>
        </div>
    </div>

    <!-- About Section -->
    <div class="info-section">
        <h2><i class="fas fa-info-circle"></i> About Our Hospital</h2>
        <p style="color: #666; line-height: 1.6;">Our hospital management system provides comprehensive healthcare solutions with:</p>
        <ul>
            <li><i class="fas fa-check-circle" style="color: #3ecd7c;"></i> Electronic Health Records (EHR) management</li>
            <li><i class="fas fa-check-circle" style="color: #3ecd7c;"></i> Appointment scheduling and management</li>
            <li><i class="fas fa-check-circle" style="color: #3ecd7c;"></i> Doctor and staff management</li>
            <li><i class="fas fa-check-circle" style="color: #3ecd7c;"></i> Patient billing and invoicing</li>
            <li><i class="fas fa-check-circle" style="color: #3ecd7c;"></i> Medical inventory management</li>
            <li><i class="fas fa-check-circle" style="color: #3ecd7c;"></i> Reporting and analytics</li>
        </ul>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Hospital Management System. All rights reserved.</p>
        <p>Designed with <i class="fas fa-heart heart"></i> for better healthcare services</p>
    </div>
</div>

</body>
</html>