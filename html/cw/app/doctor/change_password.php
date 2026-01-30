<?php
session_start();
require("../includes/db.php");

// 检查是否已登录
if (!isset($_SESSION["doctor"])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION["doctor"];
$msg = "";
$msg_type = "error";

// 生成 CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 默认测试账号密码
$default_test_passwords = [
    'mceards' => 'lord456',
    'moorland' => 'buzz48'
];

// 初始化测试账号密码(如果还没有)
if (!isset($_SESSION['test_passwords'])) {
    $_SESSION['test_passwords'] = $default_test_passwords;
}

// 检查是否是测试账号
$is_test_account = isset($default_test_passwords[$doctor_id]);

// 处理密码修改请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 验证 CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $msg = "Invalid request. Please refresh and try again.";
    } else {
        $current_password = $_POST["current_password"] ?? '';
        $new_password = $_POST["new_password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';

        // 验证输入
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $msg = "Please fill in all fields.";
        } elseif ($new_password !== $confirm_password) {
            $msg = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $msg = "New password must be at least 6 characters long.";
        } else {
            // 检查是否是测试账号
            if ($is_test_account) {
                // 测试账号 - 使用session存储的密码验证
                $current_stored_password = $_SESSION['test_passwords'][$doctor_id] ?? $default_test_passwords[$doctor_id];
                
                if ($current_password === $current_stored_password) {
                    // 更新session中的密码
                    $_SESSION['test_passwords'][$doctor_id] = $new_password;
                    $msg = "Password updated successfully!";
                    $msg_type = "success";
                } else {
                    $msg = "Current password is incorrect.";
                }
            } else {
                // 数据库账号 - 正常流程
                $stmt = $conn->prepare("SELECT * FROM doctor WHERE staffno = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("s", $doctor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 1) {
                        $doctor = $result->fetch_assoc();
                        $current_pwd_valid = false;
                        
                        // 验证当前密码
                        if (isset($doctor['password']) && !empty($doctor['password'])) {
                            if (password_get_info($doctor['password'])['algo'] !== null) {
                                $current_pwd_valid = password_verify($current_password, $doctor['password']);
                            } else {
                                $current_pwd_valid = ($current_password === $doctor['password']);
                            }
                        } else {
                            $current_pwd_valid = ($current_password === $doctor_id);
                        }
                        
                        if (!$current_pwd_valid) {
                            $msg = "Current password is incorrect.";
                        } else {
                            // 更新密码
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            
                            // 检查是否有 password 字段
                            $columns = $conn->query("SHOW COLUMNS FROM doctor LIKE 'password'");
                            if ($columns->num_rows > 0) {
                                $update_stmt = $conn->prepare("UPDATE doctor SET password = ? WHERE staffno = ?");
                                if ($update_stmt) {
                                    $update_stmt->bind_param("ss", $new_hash, $doctor_id);
                                    if ($update_stmt->execute()) {
                                        $msg = "Password updated successfully!";
                                        $msg_type = "success";
                                    } else {
                                        $msg = "Failed to update password. Please try again.";
                                    }
                                    $update_stmt->close();
                                }
                            } else {
                                $msg = "Password field does not exist in the database. Please contact administrator.";
                            }
                        }
                    } else {
                        $msg = "User not found.";
                    }
                    $stmt->close();
                } else {
                    $msg = "System error. Please try again later.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Medical System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
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
        
        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
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
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-outline {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            margin-top: 10px;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .links {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .tips {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }

        .tips h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .tips ul {
            margin-left: 20px;
        }

        .tips li {
            margin: 5px 0;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 25px;
            }

            .header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon">🔑</div>
        <h2>Change Password</h2>
        <p>Update your account password</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo htmlspecialchars($msg_type); ?>">
            <span><?php echo $msg_type === 'error' ? '⚠️' : '✓'; ?></span>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="changePasswordForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <div class="input-wrapper">
                <span class="input-icon">🔒</span>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    required 
                    autofocus
                    placeholder="Enter your current password"
                >
                <button type="button" class="toggle-password" onclick="togglePassword('current_password')">👁️</button>
            </div>
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <div class="input-wrapper">
                <span class="input-icon">🔐</span>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    required
                    placeholder="Enter new password (min 6 characters)"
                >
                <button type="button" class="toggle-password" onclick="togglePassword('new_password')">👁️</button>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <div class="input-wrapper">
                <span class="input-icon">🔐</span>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    placeholder="Re-enter new password"
                >
                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Password</button>
        <a href="doctor_dashboard.php" class="btn btn-outline">Cancel</a>
    </form>

    <div class="tips">
        <h4>💡 Password Tips:</h4>
        <ul>
            <li>Use at least 6 characters</li>
            <li>Include uppercase and lowercase letters</li>
            <li>Add numbers and special characters</li>
            <li>Don't use personal information</li>
        </ul>
    </div>

    <div class="links">
        <a href="doctor_dashboard.php">← Back to Dashboard</a>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
    } else {
        input.type = 'password';
        button.textContent = '👁️';
    }
}

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>

</body>
</html>