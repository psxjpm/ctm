<?php
require_once 'config/db.inc.php';

$pdo = getDBConnection();

echo "Starting parking permit expiry check...\n";

// 更新过期的许可状态
$stmt = $pdo->prepare("
    UPDATE parking_requests 
    SET status = 'expired'
    WHERE status = 'approved'
    AND permit_end_date IS NOT NULL
    AND permit_end_date < CURDATE()
");

$stmt->execute();
$expiredCount = $stmt->rowCount();

echo "Expired parking permits updated: $expiredCount\n";

// 记录到系统日志
if ($expiredCount > 0) {
    $logStmt = $pdo->prepare("
        INSERT INTO audit_log (user_id, action_type, table_name, record_id, new_value, ip_address)
        VALUES (0, 'SYSTEM', 'parking_requests', 'batch', ?, '127.0.0.1')
    ");
    $logStmt->execute(["Expired $expiredCount parking permits"]);
}

echo "Process completed.\n";
?>