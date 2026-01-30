<?php
session_start();
require_once '../includes/db.php';

// 记录登出到审计日志
if (isset($_SESSION["admin"])) {
    $admin_name = $_SESSION["admin"];
    
    $audit_stmt = $conn->prepare("INSERT INTO audit_log (user_type, username, action_type, description) VALUES ('admin', ?, 'LOGOUT', 'Administrator logged out')");
    $audit_stmt->bind_param("s", $admin_name);
    $audit_stmt->execute();
    $audit_stmt->close();
}

// 清除session
$_SESSION = array();
session_destroy();

// 跳转到登录页面
header("Location: admin_login.php");
exit();
?>