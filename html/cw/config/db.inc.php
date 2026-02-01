<?php
// 数据库配置常量
define('DB_HOST', 'mariadb');
define('DB_NAME', 'cw_database');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// 数据库连接函数
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// 设置当前用户ID用于审计
function setCurrentUserForAudit($userId) {
    global $pdo;
    $pdo->exec("SET @current_user_id = $userId");
}

// 记录审计日志
// 包含 操作类型 表名 记录id 旧值 新值
function logAudit($actionType, $tableName, $recordId, $oldValue = null, $newValue = null) {
    global $pdo;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, action_type, table_name, record_id, old_value, new_value, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $actionType,
            $tableName,
            $recordId,
            $oldValue,
            $newValue,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
}
?>