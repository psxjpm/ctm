<?php
session_start();
require("../includes/db.php");

$msg = "";
$msg_type = "error";

// 生成 CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 如果已登录,直接跳转到仪表板
if (isset($_SESSION["doctor"])) {
    header("Location: doctor_dashboard.php");
    exit();
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

// 处理登录请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 验证 CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $msg = "Invalid request. Please refresh and try again.";
    } else {
        $user = trim($_POST["staffno"] ?? '');
        $pass = $_POST["password"] ?? '';

        // 基本验证
        if (empty($user) || empty($pass)) {
            $msg = "Please enter both staff number and password.";
        } else {
            // 首先检查是否是测试账号
            if (isset($default_test_passwords[$user])) {
                // 使用session中存储的密码(如果已修改)
                $current_password = $_SESSION['test_passwords'][$user] ?? $default_test_passwords[$user];
                
                if ($pass === $current_password) {
                    // 测试账号登录成功
                    session_regenerate_id(true);
                    
                    $_SESSION["doctor"] = $user;
                    $_SESSION["doctor_name"] = ucfirst($user);
                    $_SESSION["login_time"] = time();
                    
                    header("Location: doctor_dashboard.php");
                    exit();
                } else {
                    $msg = "Incorrect password. Please try again.";
                }
            } else {
                // 不是测试账号,检查数据库
                $stmt = $conn->prepare("SELECT * FROM doctor WHERE staffno = ? LIMIT 1");
                
                if ($stmt) {
                    $stmt->bind_param("s", $user);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows == 1) {
                        $doctor = $result->fetch_assoc();
                        
                        // 验证密码
                        $password_valid = false;
                        
                        if (isset($doctor['password']) && !empty($doctor['password'])) {
                            if (password_get_info($doctor['password'])['algo'] !== null) {
                                $password_valid = password_verify($pass, $doctor['password']);
                            } else {
                                $password_valid = ($pass === $doctor['password']);
                            }
                        } else {
                            $password_valid = ($pass === $user);
                        }

                        if ($password_valid) {
                            session_regenerate_id(true);
                            
                            $_SESSION["doctor"] = $doctor['staffno'];
                            $_SESSION["doctor_name"] = $doctor['name'] ?? $doctor['firstname'] ?? '';
                            $_SESSION["login_time"] = time();
                            
                            header("Location: doctor_dashboard.php");
                            exit();
                        } else {
                            $msg = "Incorrect password. Please try again.";
                        }
                    } else {
                        $msg = "Doctor account does not exist.";
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
    <title>Doctor Login - Medical System</title>
    <link rel="stylesheet" href="../css/style.css">
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
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 420px;
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
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .login-header p {
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

        .password-wrapper {
            position: relative;
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
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
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

        .divider {
            margin: 0 8px;
            color: #ccc;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
            }

            .login-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <div class="icon">🏥</div>
        <h2>Doctor Login</h2>
        <p>Medical Management System</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo htmlspecialchars($msg_type); ?>">
            <span><?php echo $msg_type === 'error' ? '⚠️' : '✓'; ?></span>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        
        <div class="form-group">
            <label for="staffno">Doctor ID / Staff Number</label>
            <div class="input-wrapper">
                <span class="input-icon">👤</span>
                <input 
                    type="text" 
                    id="staffno" 
                    name="staffno" 
                    required 
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your staff number"
                    value="<?php echo isset($_POST['staffno']) ? htmlspecialchars($_POST['staffno']) : ''; ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper password-wrapper">
                <span class="input-icon">🔒</span>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
                <button type="button" class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
                    👁️
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">Login</button>
    </form>

    <div class="links">
        <a href="../index.php">← Back to Home</a>
        <span class="divider">|</span>
        <a href="forgot_password.php">Forgot Password?</a>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.toggle-password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = '👁️';
    }
}

document.querySelector('form').addEventListener('submit', function(e) {
    const staffno = document.getElementById('staffno').value.trim();
    const password = document.getElementById('password').value;
    
    if (!staffno || !password) {
        e.preventDefault();
        alert('Please fill in all fields.');
    }
});
</script>

</body>
</html>