<?php
// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 若用户未登录，重定向至登陆界面
if (!isset($_SESSION['user_id'])) {
    header('Location: /cw/login.php');
    exit;
}
// 检查用户权限，若不是管理员，重定向至医生仪表盘
function requireAdmin() {
    if ($_SESSION['user_type'] !== 'admin') {
        header('Location: /cw/doctor_dashboard.php');
        exit;
    }
}
?>