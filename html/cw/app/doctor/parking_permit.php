<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in as admin or doctor
$is_admin = isset($_SESSION["admin"]);
$is_doctor = isset($_SESSION["doctor"]);

if (!$is_admin && !$is_doctor) {
    header("Location: doctor_login.php");
    exit();
}

// Set user info
if ($is_admin) {
    $current_user = $_SESSION["admin"];
    $user_role = "Administrator";
    $doctor_staff_num = null;
} else {
    $current_user = $_SESSION["doctor"];
    $user_role = "Doctor";
    $doctor_staff_num = $_SESSION["doctor"];
}

// Handle form submission for new permit application (doctor only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["apply"]) && $is_doctor) {
    $vehicle_reg = trim($_POST["vehicle_reg"]);
    $vehicle_make = trim($_POST["vehicle_make"]);
    $vehicle_model = trim($_POST["vehicle_model"]);
    $justification = trim($_POST["justification"]);
    
    $query = "INSERT INTO parking_permits (staff_num, vehicle_reg, vehicle_make, vehicle_model, justification, status, application_date) 
              VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $doctor_staff_num, $vehicle_reg, $vehicle_make, $vehicle_model, $justification);
    
    if ($stmt->execute()) {
        $success_msg = "Parking permit application submitted successfully!";
    } else {
        $error_msg = "Error submitting application. Please try again.";
    }
}

// Fetch parking permits based on user role
if ($is_admin) {
    // Admin sees ALL parking permits with doctor information
    $query = "SELECT pp.*, d.first_name, d.last_name, d.specialisation 
              FROM parking_permits pp 
              LEFT JOIN doctor d ON pp.staff_num = d.staff_num 
              ORDER BY pp.permit_id DESC";
    $stmt = $conn->prepare($query);
} else {
    // Doctor sees only own parking permits
    $query = "SELECT * FROM parking_permits WHERE staff_num = ? ORDER BY permit_id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $doctor_staff_num);
}

$stmt->execute();
$result = $stmt->get_result();
$permits = [];

while ($row = $result->fetch_assoc()) {
    $permits[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Permit</title>
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
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .admin-badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .doctor-badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(135deg, #56ccf2 0%, #2f80ed 100%);
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .back-link {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .section-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .no-permits {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .no-permits-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        th:first-child {
            border-top-left-radius: 10px;
        }

        th:last-child {
            border-top-right-radius: 10px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .permit-number {
            font-weight: bold;
            color: #667eea;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div>
            <h1>🅿️ Parking Permit</h1>
            <div class="user-info">
                Viewing as: <?php echo htmlspecialchars($current_user); ?>
                <?php if ($is_admin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php else: ?>
                    <span class="doctor-badge">DOCTOR</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($is_admin): ?>
            <a href="../admin/admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        <?php else: ?>
            <a href="doctor_dashboard.php" class="back-link">← Back to Doctor Dashboard</a>
        <?php endif; ?>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Application Form (Doctor Only) -->
    <?php if ($is_doctor): ?>
    <div class="content">
        <div class="section-title">
            <span>📝</span>
            <span>Apply for Parking Permit</span>
        </div>
        <form method="post">
            <div class="form-group">
                <label>Vehicle Registration Number *</label>
                <input type="text" name="vehicle_reg" required placeholder="e.g., ABC-123">
            </div>
            <div class="form-group">
                <label>Vehicle Make *</label>
                <input type="text" name="vehicle_make" required placeholder="e.g., Toyota">
            </div>
            <div class="form-group">
                <label>Vehicle Model *</label>
                <input type="text" name="vehicle_model" required placeholder="e.g., Camry">
            </div>
            <div class="form-group">
                <label>Justification *</label>
                <textarea name="justification" rows="3" required placeholder="Please explain why you need a parking permit..."></textarea>
            </div>
            <button type="submit" name="apply" class="btn-submit">Submit Application</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="content">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($permits); ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($permits, function($p) { return $p['status'] == 'Pending'; })); ?>
                </div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($permits, function($p) { return $p['status'] == 'Approved'; })); ?>
                </div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php echo count(array_filter($permits, function($p) { return $p['status'] == 'Rejected'; })); ?>
                </div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Permits List -->
        <div class="section-title">
            <span>📋</span>
            <span><?php echo $is_admin ? 'All Parking Permits' : 'My Parking Permits'; ?></span>
        </div>

        <?php if (empty($permits)): ?>
            <div class="no-permits">
                <div class="no-permits-icon">🅿️</div>
                <h3>No parking permits found</h3>
                <p style="margin-top: 10px; color: #666;">
                    <?php if ($is_doctor): ?>
                        You haven't submitted any parking permit applications yet.
                    <?php else: ?>
                        No parking permit applications in the system.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($is_admin): ?>
                            <th>Doctor</th>
                        <?php endif; ?>
                        <th>Vehicle Reg</th>
                        <th>Vehicle</th>
                        <th>Justification</th>
                        <th>Status</th>
                        <th>Permit Number</th>
                        <th>Applied Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permits as $permit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($permit['permit_id']); ?></td>
                        <?php if ($is_admin): ?>
                            <td>
                                <?php 
                                if (isset($permit['first_name'])) {
                                    echo htmlspecialchars($permit['first_name'] . ' ' . $permit['last_name']);
                                    if (isset($permit['specialisation'])) {
                                        echo '<br><small style="color: #999;">' . htmlspecialchars($permit['specialisation']) . '</small>';
                                    }
                                } else {
                                    echo htmlspecialchars($permit['staff_num']);
                                }
                                ?>
                            </td>
                        <?php endif; ?>
                        <td><strong><?php echo htmlspecialchars($permit['vehicle_reg']); ?></strong></td>
                        <td><?php echo htmlspecialchars($permit['vehicle_make'] . ' ' . $permit['vehicle_model']); ?></td>
                        <td><?php echo htmlspecialchars($permit['justification']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($permit['status']); ?>">
                                <?php echo htmlspecialchars($permit['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if (!empty($permit['permit_number'])) {
                                echo '<span class="permit-number">' . htmlspecialchars($permit['permit_number']) . '</span>';
                            } else {
                                echo '<span style="color: #999;">—</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($permit['application_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>