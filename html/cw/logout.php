<?php
// 启动会话
session_start();

// 如果用户已登录，记录注销审计日志
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.inc.php';
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, action_type, table_name, record_id, ip_address)
            VALUES (?, 'LOGOUT', 'users', ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {
        // 静默处理审计日志错误
    }
}

// 清除所有会话数据
$_SESSION = array();

// 删除会话cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到登录页面
header("Location: /cw/login.php");
exit;
?>