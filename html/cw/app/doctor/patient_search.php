<?php
session_start();
require("../includes/db.php");

// 检查是否已登录
if (!isset($_SESSION["doctor"])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION["doctor"];
$doctor_name = $_SESSION["doctor_name"] ?? $doctor_id;

$search_term = "";
$patient = null;
$admissions = [];
$tests = [];
$msg = "";
$msg_type = "info";

// 生成 CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 处理搜索请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $msg = "Invalid request. Please refresh and try again.";
        $msg_type = "error";
    } else {
        $action = $_POST['action'] ?? 'search';
        
        // 添加新患者
        if ($action == 'add_patient') {
            $nhs_no = trim($_POST['nhs_no'] ?? '');
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $age = intval($_POST['age'] ?? 0);
            $gender = $_POST['gender'] ?? '';
            $emergency_phone = trim($_POST['emergency_phone'] ?? '');
            
            if (empty($nhs_no) || empty($firstname) || empty($lastname) || empty($phone) || empty($address) || $age <= 0) {
                $msg = "Please fill in all required fields.";
                $msg_type = "error";
                $search_term = $nhs_no;
            } else {
                // 插入新患者
                $insert_stmt = $conn->prepare("
                    INSERT INTO patient (NHSno, firstname, lastname, phone, address, age, gender, emergencyphone) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->bind_param("sssssiss", $nhs_no, $firstname, $lastname, $phone, $address, $age, $gender, $emergency_phone);
                
                if ($insert_stmt->execute()) {
                    // 重新获取患者信息
                    $stmt = $conn->prepare("SELECT * FROM patient WHERE NHSno = ?");
                    $stmt->bind_param("s", $nhs_no);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $patient = $result->fetch_assoc();
                    $stmt->close();
                    
                    // 获取admission和test记录(新患者应该没有)
                    $admissions = [];
                    $tests = [];
                    
                    $msg = "Patient added successfully!";
                    $msg_type = "success";
                } else {
                    $msg = "Error adding patient. NHS number may already exist.";
                    $msg_type = "error";
                    $search_term = $nhs_no;
                }
                $insert_stmt->close();
            }
        }
        // 搜索患者
        else {
            $search_term = trim($_POST["search_term"] ?? '');
            
            if (empty($search_term)) {
                $msg = "Please enter a patient name or NHS number.";
                $msg_type = "error";
            } else {
            // 搜索患者 - 支持姓名或NHS编号
            $stmt = $conn->prepare("
                SELECT * FROM patient 
                WHERE NHSno = ? 
                OR CONCAT(firstname, ' ', lastname) LIKE ? 
                OR firstname LIKE ? 
                OR lastname LIKE ?
                LIMIT 1
            ");
            
            if ($stmt) {
                $search_like = "%{$search_term}%";
                $stmt->bind_param("ssss", $search_term, $search_like, $search_like, $search_like);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $patient = $result->fetch_assoc();
                    
                    // 查询住院记录 - wardpatientaddmission 表
                    $admission_tables_check = $conn->query("SHOW TABLES LIKE 'wardpatientaddmission'");
                    if ($admission_tables_check && $admission_tables_check->num_rows > 0) {
                        $admission_stmt = $conn->prepare("
                            SELECT w.*, wd.wardname, wd.address as ward_address, 
                                   d.firstname as doctor_firstname, d.lastname as doctor_lastname
                            FROM wardpatientaddmission w
                            LEFT JOIN ward wd ON w.wardid = wd.wardid
                            LEFT JOIN doctor d ON w.consultantid = d.staffno
                            WHERE w.pid = ?
                            ORDER BY w.date DESC, w.time DESC
                        ");
                        
                        if ($admission_stmt) {
                            $admission_stmt->bind_param("s", $patient['NHSno']);
                            $admission_stmt->execute();
                            $admission_result = $admission_stmt->get_result();
                            
                            while ($row = $admission_result->fetch_assoc()) {
                                $admissions[] = $row;
                            }
                            $admission_stmt->close();
                        }
                    }
                    
                    // 查询检测记录 - patient_test 表
                    $test_tables_check = $conn->query("SHOW TABLES LIKE 'patient_test'");
                    if ($test_tables_check && $test_tables_check->num_rows > 0) {
                        $test_stmt = $conn->prepare("
                            SELECT pt.*, t.testname,
                                   d.firstname as doctor_firstname, d.lastname as doctor_lastname
                            FROM patient_test pt
                            LEFT JOIN test t ON pt.testid = t.testid
                            LEFT JOIN doctor d ON pt.doctorid = d.staffno
                            WHERE pt.pid = ?
                            ORDER BY pt.date DESC
                        ");
                        
                        if ($test_stmt) {
                            $test_stmt->bind_param("s", $patient['NHSno']);
                            $test_stmt->execute();
                            $test_result = $test_stmt->get_result();
                            
                            while ($row = $test_result->fetch_assoc()) {
                                $tests[] = $row;
                            }
                            $test_stmt->close();
                        }
                    }
                    
                    // 设置成功消息
                    if (empty($admissions) && empty($tests)) {
                        $msg = "Patient found in system, but no admission or test records available.";
                        $msg_type = "warning";
                    } else {
                        $msg = "Patient information retrieved successfully.";
                        $msg_type = "success";
                    }
                    
                } else {
                    $msg = "Patient not found. Please check the name or NHS number and try again.";
                    $msg_type = "error";
                }
                
                $stmt->close();
            } else {
                $msg = "Database error. Please try again later.";
                $msg_type = "error";
            }
        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Information - Medical System</title>
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
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

        .section h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.error {
            background-color: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert.success {
            background-color: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        .alert.warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert.info {
            background-color: #e3f2fd;
            color: #0c5460;
            border-left: 4px solid #2196f3;
        }

        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
        }

        .search-form input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .patient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .patient-card h3 {
            font-size: 26px;
            margin-bottom: 15px;
        }

        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .no-data {
            color: #999;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .patient-info {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔍 Patient Information System</h1>
        <p>Search and view patient records, admissions, and test results</p>
    </div>

    <div class="section">
        <h2>Search Patient</h2>
        
        <form method="post" class="search-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input 
                type="text" 
                name="search_term" 
                placeholder="Enter patient name or NHS number..."
                value="<?php echo htmlspecialchars($search_term); ?>"
                autofocus
                required
            >
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="doctor_dashboard.php" class="btn btn-outline">← Back</a>
        </form>

        <?php if (!empty($msg)): ?>
            <div class="alert <?php echo htmlspecialchars($msg_type); ?>">
                <span><?php 
                    echo $msg_type === 'error' ? '⚠️' : 
                        ($msg_type === 'success' ? '✓' : 
                        ($msg_type === 'warning' ? '⚡' : 'ℹ️')); 
                ?></span>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($patient): ?>
        <!-- Patient Information Card -->
        <div class="patient-card">
            <h3>👤 <?php echo htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']); ?></h3>
            <div class="patient-info">
                <div class="info-item">
                    <div class="info-label">NHS Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['NHSno']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Age</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['age'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?php 
                        $gender = isset($patient['gender']) ? $patient['gender'] : 'N/A';
                        echo htmlspecialchars($gender == '1' ? 'Female' : ($gender == '0' ? 'Male' : $gender)); 
                    ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></div>
                </div>
                <?php if (!empty($patient['emergencyphone'])): ?>
                <div class="info-item">
                    <div class="info-label">Emergency Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['emergencyphone']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($admissions); ?></div>
                <div class="stat-label">Ward Admissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($tests); ?></div>
                <div class="stat-label">Tests Performed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $currently_admitted = array_filter($admissions, function($a) {
                        return $a['status'] == 0; // status=0 表示仍在住院
                    });
                    echo count($currently_admitted) > 0 ? '✓' : '✗';
                    ?>
                </div>
                <div class="stat-label">Currently Admitted</div>
            </div>
        </div>

        <!-- Ward Admission Records -->
        <div class="section">
            <h2>🏥 Ward Admission Records</h2>
            
            <?php if (empty($admissions)): ?>
                <div class="no-data">
                    ℹ️ No ward admission records found. This patient has not been admitted to any ward.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ward</th>
                            <th>Consultant</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admissions as $admission): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($admission['wardname'] ?? 'Unknown Ward'); ?></strong>
                                    <?php if (!empty($admission['ward_address'])): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars($admission['ward_address']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($admission['doctor_firstname'])) {
                                        echo 'Dr. ' . htmlspecialchars($admission['doctor_firstname'] . ' ' . $admission['doctor_lastname']);
                                    } else {
                                        echo htmlspecialchars($admission['consultantid']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($admission['date'])) {
                                        echo date('d M Y', strtotime($admission['date']));
                                    } else {
                                        echo '<span class="no-data">Not recorded</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo !empty($admission['time']) ? htmlspecialchars($admission['time']) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php if ($admission['status'] == 0): ?>
                                        <span class="badge badge-warning">Currently Admitted</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Discharged</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Test Records -->
        <div class="section">
            <h2>🧪 Test Records</h2>
            
            <?php if (empty($tests)): ?>
                <div class="no-data">
                    ℹ️ No test records found. This patient has not undergone any medical tests.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Doctor</th>
                            <th>Test Date</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($test['testname'] ?? 'Test #' . $test['testid']); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($test['doctor_firstname'])) {
                                        echo 'Dr. ' . htmlspecialchars($test['doctor_firstname'] . ' ' . $test['doctor_lastname']);
                                    } else {
                                        echo htmlspecialchars($test['doctorid'] ?? 'N/A');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($test['date'])) {
                                        echo date('d M Y', strtotime($test['date']));
                                    } else {
                                        echo '<span class="no-data">Not recorded</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($test['report'])) {
                                        echo htmlspecialchars($test['report']);
                                    } else {
                                        echo '<span class="badge badge-warning">Pending</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($search_term)): ?>
        <!-- No Patient Found - Add New Patient Form -->
        <div class="section">
            <div class="alert error">
                <span>⚠️</span>
                <span>Patient not found. You can add this patient to the system below.</span>
            </div>

            <h2>➕ Add New Patient</h2>
            <p style="margin-bottom: 25px; color: #666;">
                No patient found with NHS number: <strong><?php echo htmlspecialchars($search_term); ?></strong>
            </p>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add_patient">
                <input type="hidden" name="nhs_no" value="<?php echo htmlspecialchars($search_term); ?>">
                
                <div class="patient-card" style="margin-bottom: 30px;">
                    <h3>NHS Number: <?php echo htmlspecialchars($search_term); ?></h3>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div class="form-group">
                        <label for="firstname">First Name *</label>
                        <input 
                            type="text" 
                            id="firstname" 
                            name="firstname" 
                            placeholder="Enter first name"
                            required
                            style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;"
                        >
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last Name *</label>
                        <input 
                            type="text" 
                            id="lastname" 
                            name="lastname" 
                            placeholder="Enter last name"
                            required
                            style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;"
                        >
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div class="form-group">
                        <label for="age">Age *</label>
                        <input 
                            type="number" 
                            id="age" 
                            name="age" 
                            placeholder="Enter age"
                            min="0"
                            max="150"
                            required
                            style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;"
                        >
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select 
                            id="gender" 
                            name="gender" 
                            required
                            style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;"
                        >
                            <option value="">Select gender</option>
                            <option value="0">Male</option>
                            <option value="1">Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="phone">Phone Number *</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="Enter phone number"
                        required
                        style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;"
                    >
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="address">Address *</label>
                    <textarea 
                        id="address" 
                        name="address" 
                        rows="3"
                        placeholder="Enter full address"
                        required
                        style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical;"
                    ></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="emergency_phone">Emergency Contact Phone</label>
                    <input 
                        type="tel" 
                        id="emergency_phone" 
                        name="emergency_phone" 
                        placeholder="Enter emergency contact number (optional)"
                        style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px;"
                    >
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Optional field</div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary">✓ Add Patient to System</button>
                    <a href="patient_search.php" class="btn btn-outline">← Search Again</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Initial State -->
        <div class="section">
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <h3>Search for a Patient</h3>
                <p>Enter a patient's name or NHS number in the search box above to view their information.</p>
            </div>
        </div>
    <?php endif; ?>

    <a href="doctor_dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>