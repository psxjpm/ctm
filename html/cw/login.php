<?php
// 启动会话
session_start();
require_once 'config/db.inc.php';

// 如果用户已登录，根据用户类型重定向到相应仪表板
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: /cw/admin_dashboard.php');
    } else {
        header('Location: /cw/doctor_dashboard.php');
    }
    exit;
}

$error = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $pdo = getDBConnection();
    
    // 查询用户信息
    $stmt = $pdo->prepare("
        SELECT u.*, d.Doctor_id, d.FirstName, d.LastName, d.Staff_no
        FROM users u 
        LEFT JOIN DOCTOR d ON u.doctor_id = d.Doctor_id 
        WHERE u.username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // 验证用户凭据
    if ($user && $user['password'] === $password) {
        // 设置会话变量
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        
        if ($user['user_type'] === 'doctor' && isset($user['Doctor_id'])) {
            $_SESSION['doctor_id'] = $user['Doctor_id'];
            $_SESSION['doctor_name'] = $user['FirstName'] . ' ' . $user['LastName'];
        } elseif ($user['user_type'] === 'admin') {
            $_SESSION['doctor_id'] = null;
            $_SESSION['doctor_name'] = 'Administrator';
        }
        
        // 记录登录审计日志
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_log (user_id, action_type, table_name, record_id, ip_address, user_agent)
                VALUES (?, 'LOGIN', 'users', ?, ?, ?)
            ");
            $stmt->execute([
                $user['user_id'],
                $user['user_id'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (Exception $e) {
            // 静默处理审计日志错误
        }
        
        // 根据用户类型重定向仪表盘
        if ($user['user_type'] === 'admin') {
            header('Location: /cw/admin_dashboard.php');
        } else {
            header('Location: /cw/doctor_dashboard.php');
        }
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QMC Hospital System - Login</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <!-- 登录容器 -->
    <div class="login-container">
        <div class="text-center">
            <h1>Queen's Medical Centre</h1>
            <p>Hospital Management System</p>
        </div>
        
        <!-- 错误消息显示 -->
        <?php if ($error !== ''): ?>
            <div class="message error mt-15">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- 登录表单 -->
        <h2 class="text-center">User Login</h2>
        
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       placeholder="Enter your username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                       required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Enter your password" 
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">Sign In</button>
        </form>
    </div>
</body>
</html>