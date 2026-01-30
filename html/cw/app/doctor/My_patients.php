<?php
session_start();
require_once '../includes/db.php';

// 检查医生是否已登录
if (!isset($_SESSION["doctor"])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION["doctor"];
$msg = "";
$msg_type = "";

// 生成 CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 获取所有检查项目
$tests = [];
$test_stmt = $conn->query("SELECT testid, testname FROM test ORDER BY testname");
while ($row = $test_stmt->fetch_assoc()) {
    $tests[] = $row;
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $msg = "Invalid request. Please refresh and try again.";
        $msg_type = "error";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add_patient') {
            // 添加新患者
            $nhs_no = strtoupper(trim($_POST['nhs_no'] ?? ''));
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $age = intval($_POST['age'] ?? 0);
            $gender = $_POST['gender'] ?? '';
            $emergency_phone = trim($_POST['emergency_phone'] ?? '');
            
            if (empty($nhs_no) || empty($firstname) || empty($phone) || empty($address) || $age <= 0) {
                $msg = "Please fill in all required fields.";
                $msg_type = "error";
            } else {
                // 检查NHS号是否已存在
                $check_stmt = $conn->prepare("SELECT NHSno FROM patient WHERE NHSno = ?");
                $check_stmt->bind_param("s", $nhs_no);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $msg = "Patient with NHS Number $nhs_no already exists.";
                    $msg_type = "error";
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO patient (NHSno, firstname, lastname, phone, address, age, gender, emergencyphone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("sssssiss", $nhs_no, $firstname, $lastname, $phone, $address, $age, $gender, $emergency_phone);
                    if ($insert_stmt->execute()) {
                        $msg = "Patient added successfully! NHS No: $nhs_no";
                        $msg_type = "success";
                    } else {
                        $msg = "Error adding patient.";
                        $msg_type = "error";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        } elseif ($action == 'order_test') {
            // 开具检查
            $patient_nhs = strtoupper(trim($_POST['patient_nhs'] ?? ''));
            $test_id = intval($_POST['test_id'] ?? 0);
            $test_date = trim($_POST['test_date'] ?? '');
            
            if (empty($patient_nhs) || $test_id <= 0 || empty($test_date)) {
                $msg = "Please fill in all required fields.";
                $msg_type = "error";
            } else {
                // 检查患者是否存在
                $check_patient = $conn->prepare("SELECT NHSno, firstname, lastname FROM patient WHERE NHSno = ?");
                $check_patient->bind_param("s", $patient_nhs);
                $check_patient->execute();
                $patient_result = $check_patient->get_result();
                
                if ($patient_result->num_rows == 0) {
                    $msg = "Patient with NHS Number $patient_nhs not found. Please add the patient first.";
                    $msg_type = "error";
                } else {
                    $patient_data = $patient_result->fetch_assoc();
                    
                    // 插入检查记录
                    $insert_test = $conn->prepare("INSERT INTO patient_test (pid, testid, date, doctorid) VALUES (?, ?, ?, ?)");
                    $insert_test->bind_param("siss", $patient_nhs, $test_id, $test_date, $doctor_id);
                    
                    if ($insert_test->execute()) {
                        $msg = "Test ordered successfully for patient: " . $patient_data['firstname'] . " " . $patient_data['lastname'];
                        $msg_type = "success";
                    } else {
                        $msg = "Error ordering test.";
                        $msg_type = "error";
                    }
                    $insert_test->close();
                }
                $check_patient->close();
            }
        }
    }
}

// 获取最近开具的检查记录
$recent_tests = [];
$recent_stmt = $conn->prepare("
    SELECT pt.pid, pt.testid, pt.date, pt.report, 
           p.firstname, p.lastname, t.testname
    FROM patient_test pt
    JOIN patient p ON pt.pid = p.NHSno
    JOIN test t ON pt.testid = t.testid
    WHERE pt.doctorid = ?
    ORDER BY pt.date DESC
    LIMIT 10
");
$recent_stmt->bind_param("s", $doctor_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
while ($row = $recent_result->fetch_assoc()) {
    $recent_tests[] = $row;
}
$recent_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Medical Tests - Hospital Management System</title>
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
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .header p {
            color: #666;
            margin-top: 5px;
        }

        .back-link {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid #667eea;
        }

        .back-link:hover {
            background: #667eea;
            color: white;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            padding: 15px 30px;
            background: none;
            border: none;
            color: #666;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab:hover {
            color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
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
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background: #667eea;
            color: white;
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }

        .search-box button {
            position: absolute;
            right: 5px;
            top: 5px;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h1>🩺 Order Medical Tests</h1>
            <p>Add patients and order medical tests</p>
        </div>
        <a href="doctor_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo htmlspecialchars($msg_type); ?>">
            <span><?php echo $msg_type === 'error' ? '⚠️' : '✓'; ?></span>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- 标签页 -->
    <div class="section">
        <div class="tabs">
            <button class="tab active" onclick="showTab('order')">📋 Order Test</button>
            <button class="tab" onclick="showTab('addpatient')">👤 Add New Patient</button>
            <button class="tab" onclick="showTab('recent')">📊 Recent Tests</button>
        </div>

        <!-- 开具检查 -->
        <div id="order" class="tab-content active">
            <h2>Order Medical Test</h2>
            
            <div class="info-card">
                <strong>💡 Instructions:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Enter the patient's NHS Number</li>
                    <li>If patient doesn't exist, add them first using "Add New Patient" tab</li>
                    <li>Select the test and date</li>
                </ul>
            </div>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="order_test">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="patient_nhs">Patient NHS Number *</label>
                        <input type="text" id="patient_nhs" name="patient_nhs" placeholder="e.g., W20616" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label for="test_id">Medical Test *</label>
                        <select id="test_id" name="test_id" required>
                            <option value="">-- Select Test --</option>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?php echo $test['testid']; ?>">
                                    <?php echo htmlspecialchars($test['testname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="test_date">Test Date *</label>
                        <input type="date" id="test_date" name="test_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">✓ Order Test</button>
            </form>
        </div>

        <!-- 添加新患者 -->
        <div id="addpatient" class="tab-content">
            <h2>Add New Patient</h2>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add_patient">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nhs_no">NHS Number *</label>
                        <input type="text" id="nhs_no" name="nhs_no" placeholder="e.g., W20616" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label for="firstname">First Name *</label>
                        <input type="text" id="firstname" name="firstname" placeholder="Enter first name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" placeholder="Enter last name">
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age *</label>
                        <input type="number" id="age" name="age" placeholder="Enter age" required min="0" max="150">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">-- Select Gender --</option>
                            <option value="0">Male</option>
                            <option value="1">Female</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g., 07656999653" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="2" placeholder="Enter full address" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_phone">Emergency Contact Phone</label>
                        <input type="tel" id="emergency_phone" name="emergency_phone" placeholder="Emergency contact number">
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary">✓ Add Patient</button>
                    <button type="reset" class="btn btn-outline">↺ Reset Form</button>
                </div>
            </form>
        </div>

        <!-- 最近开具的检查 -->
        <div id="recent" class="tab-content">
            <h2>Recent Tests Ordered</h2>
            
            <?php if (empty($recent_tests)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Tests Ordered Yet</h3>
                    <p>Start by ordering medical tests for your patients!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient NHS No</th>
                            <th>Patient Name</th>
                            <th>Test Name</th>
                            <th>Report Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tests as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['date']) ?: 'Not scheduled'; ?></td>
                                <td><strong><?php echo htmlspecialchars($test['pid']); ?></strong></td>
                                <td><?php echo htmlspecialchars($test['firstname'] . ' ' . $test['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($test['testname']); ?></td>
                                <td>
                                    <?php if (empty($test['report'])): ?>
                                        <span style="color: #856404; background: #fff3cd; padding: 4px 12px; border-radius: 12px; font-size: 12px;">⏳ Pending</span>
                                    <?php else: ?>
                                        <span style="color: #155724; background: #d4edda; padding: 4px 12px; border-radius: 12px; font-size: 12px;">✓ Complete</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 标签页切换
function showTab(tabName) {
    // 隐藏所有内容
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.classList.remove('active'));
    
    // 移除所有标签的active类
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // 显示选中的内容
    document.getElementById(tabName).classList.add('active');
    
    // 添加active类到对应标签
    event.target.classList.add('active');
}

// NHS号自动转大写
document.getElementById('patient_nhs').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

document.getElementById('nhs_no').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>

</body>
</html>