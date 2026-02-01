<?php
require_once '../config/db.inc.php';
require_once '../includes/auth_check.php';

// 确保只有管理员可以访问
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: /cw/doctor_dashboard.php');
    exit;
}

$pdo = getDBConnection();

// 筛选参数
$userId = $_GET['user_id'] ?? null;
$actionType = $_GET['action_type'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// 构建查询
$sql = "
    SELECT al.*, u.username, u.user_type
    FROM audit_log al
    JOIN users u ON al.user_id = u.user_id
    WHERE 1=1
";

$params = [];

// 添加筛选条件
if ($userId) {
    $sql .= " AND al.user_id = ?";
    $params[] = $userId;
}

if ($actionType) {
    $sql .= " AND al.action_type = ?";
    $params[] = $actionType;
}

if ($startDate) {
    $sql .= " AND DATE(al.timestamp) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND DATE(al.timestamp) <= ?";
    $params[] = $endDate;
}

$sql .= " ORDER BY al.timestamp DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$auditLogs = $stmt->fetchAll();

// 获取用户列表用于筛选
$usersStmt = $pdo->query("SELECT user_id, username FROM users ORDER BY username");
$users = $usersStmt->fetchAll();

// 获取动作类型列表
$actionsStmt = $pdo->query("SELECT DISTINCT action_type FROM audit_log ORDER BY action_type");
$actions = $actionsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - QMC Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Audit Trail</h1>
        
        <!-- 筛选表单 -->
        <form method="GET" class="card">
            <div class="form-row">
                <div class="form-group">
                    <label>User:</label>
                    <select name="user_id">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>" 
                                <?php echo ($userId == $user['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Action Type:</label>
                    <select name="action_type">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                        <option value="<?php echo $action['action_type']; ?>" 
                                <?php echo ($actionType == $action['action_type']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action['action_type']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                
                <div class="form-group">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
            </div>
            
            <div class="mt-15">
                <button type="submit" class="btn btn-primary btn-medium">Filter</button>
                <a href="audit_trail.php" class="btn btn-secondary btn-medium">Clear Filters</a>
            </div>
        </form>
        
        <!-- 审计日志表格 -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogs as $log): ?>
                    <tr>
                        <td><?php echo $log['timestamp']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($log['username']); ?>
                            <small>(<?php echo $log['user_type']; ?>)</small>
                        </td>
                        <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                        <td><?php echo $log['record_id']; ?></td>
                        <td class="audit-details">
                            <?php if ($log['old_value']): ?>
                                <div><strong>Old:</strong> <?php echo htmlspecialchars(substr($log['old_value'], 0, 100)); ?></div>
                            <?php endif; ?>
                            <?php if ($log['new_value']): ?>
                                <div><strong>New:</strong> <?php echo htmlspecialchars(substr($log['new_value'], 0, 100)); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($auditLogs)): ?>
                <div class="empty-message">No audit logs found</div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>