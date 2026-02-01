<?php
require_once '../config/db.inc.php';
require_once '../includes/auth_check.php';

// 仅管理员可访问
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: /cw/doctor_dashboard.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$error = '';

// 获取病房列表用于下拉菜单
$wardsStmt = $pdo->query("SELECT Ward_id, Name FROM WARD ORDER BY Name");
$wards = $wardsStmt->fetchAll();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 收集医生信息
        $doctorData = [
            'FirstName' => $_POST['first_name'],
            'LastName' => $_POST['last_name'],
            'Specialisation' => $_POST['specialisation'],
            'Qualification' => $_POST['qualification'],
            'Pay' => $_POST['pay'],
            'Gender' => $_POST['gender'],
            'Address_city' => $_POST['city'],
            'Address_street' => $_POST['street'],
            'Address_code' => $_POST['postcode'],
            'Ward_id' => $_POST['ward_id'],
            'Staff_no' => $_POST['staff_no']
        ];
        
        // 验证员工号唯一性
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM DOCTOR WHERE Staff_no = ?");
        $checkStmt->execute([$doctorData['Staff_no']]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Staff number already exists");
        }
        
        // 插入医生记录
        $doctorSql = "INSERT INTO DOCTOR (FirstName, LastName, Specialisation, Qualification, 
                     Pay, Gender, Address_city, Address_street, Address_code, Ward_id, Staff_no) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $doctorStmt = $pdo->prepare($doctorSql);
        $doctorStmt->execute(array_values($doctorData));
        
        $doctorId = $pdo->lastInsertId();
        
        // 创建用户账户
        $username = strtolower(substr($doctorData['FirstName'], 0, 1) . $doctorData['LastName']);
        $baseUsername = $username;
        $counter = 1;
        
        // 确保用户名唯一
        while (true) {
            $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $checkUser->execute([$username]);
            if ($checkUser->fetchColumn() == 0) {
                break;
            }
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // 默认密码
        $password = 'welcome123';
        
        $userSql = "INSERT INTO users (username, password, user_type, doctor_id) 
                    VALUES (?, ?, 'doctor', ?)";
        $userStmt = $pdo->prepare($userSql);
        $userStmt->execute([$username, $password, $doctorId]);
        
        // 记录审计日志
        logAudit('CREATE', 'DOCTOR', $doctorId, null, json_encode($doctorData));
        logAudit('CREATE', 'users', $pdo->lastInsertId(), null, json_encode(['username' => $username, 'user_type' => 'doctor']));
        
        $pdo->commit();
        
        $message = "Doctor account created successfully!<br>";
        $message .= "Username: <strong>$username</strong><br>";
        $message .= "Password: <strong>$password</strong><br>";
        $message .= "Please inform the doctor to change their password immediately.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error creating doctor: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Doctor - QMC Admin</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Create New Doctor Account</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <!-- 显示成功消息 -->
            <div class="message success">
                <?php echo $message; ?>
                <div class="mt-15">
                    <a href="/cw/admin/create_doctor.php" class="btn btn-primary">Create Another</a>
                    <a href="/cw/admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
        
        <!-- 密码说明 -->
        <div class="card">
            <p><strong>Note:</strong> A default password will be generated. The doctor should change it on first login.</p>
        </div>
        
        <!-- 医生账户创建表单 -->
        <form method="POST" class="card">
            <!-- 个人信息部分 -->
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Staff Number (NHS)</label>
                        <input type="text" name="staff_no" 
                               placeholder="e.g., DOC123456">
                    </div>
                </div>
            </div>
            
            <!-- 专业信息部分 -->
            <div class="form-section">
                <h3>Professional Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Specialisation</label>
                        <input type="text" name="specialisation"
                               placeholder="e.g., Cardiology, Orthopedics">
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification"
                               placeholder="e.g., MD, PhD, MBBS">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Annual Salary (£)</label>
                        <input type="number" name="pay" min="30000" max="200000" step="1000"
                               placeholder="e.g., 75000">
                    </div>
                    <div class="form-group">
                        <label>Assigned Ward</label>
                        <select name="ward_id">
                            <option value="">Select Ward</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value="<?php echo $ward['Ward_id']; ?>">
                                    <?php echo htmlspecialchars($ward['Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- 地址信息部分 -->
            <div class="form-section">
                <h3>Address Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Street Address</label>
                        <input type="text" name="street"
                               placeholder="e.g., 123 Main Street">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city"
                               placeholder="e.g., Nottingham">
                    </div>
                    <div class="form-group">
                        <label>Postcode</label>
                        <input type="text" name="postcode"
                               placeholder="e.g., NG7 2RD">
                    </div>
                </div>
            </div>
            
            <!-- 账户信息部分 -->
            <div class="form-section">
                <h3>Account Information</h3>
                <div class="card">
                    <p><strong>Automatic Account Creation:</strong></p>
                    <ul class="note-list-ul">
                        <li>Username will be generated from first initial + last name</li>
                        <li>Default password: <strong>welcome123</strong></li>
                        <li>Doctor must change password on first login</li>
                    </ul>
                </div>
            </div>
            
            <!-- 提交按钮 -->
            <div class="mt-30 text-center">
                <button type="submit" class="btn btn-primary btn-medium">
                    Create Doctor Account
                </button>
                <a href="/cw/admin_dashboard.php" class="btn btn-secondary btn-medium">Cancel</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>