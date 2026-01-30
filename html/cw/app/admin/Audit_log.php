<?php
session_start();
require_once '../includes/db.php';

// 检查管理员是否已登录
if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin"];

// 获取筛选参数
$filter_user_type = $_GET['user_type'] ?? 'all';
$filter_action_type = $_GET['action_type'] ?? 'all';
$filter_username = $_GET['username'] ?? '';
$filter_table = $_GET['table'] ?? 'all';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 构建查询
$where_clauses = [];
$params = [];
$types = '';

if ($filter_user_type !== 'all') {
    $where_clauses[] = "user_type = ?";
    $params[] = $filter_user_type;
    $types .= 's';
}

if ($filter_action_type !== 'all') {
    $where_clauses[] = "action_type = ?";
    $params[] = $filter_action_type;
    $types .= 's';
}

if (!empty($filter_username)) {
    $where_clauses[] = "username LIKE ?";
    $params[] = "%$filter_username%";
    $types .= 's';
}

if ($filter_table !== 'all') {
    $where_clauses[] = "table_name = ?";
    $params[] = $filter_table;
    $types .= 's';
}

if (!empty($filter_date_from)) {
    $where_clauses[] = "DATE(action_timestamp) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if (!empty($filter_date_to)) {
    $where_clauses[] = "DATE(action_timestamp) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// 获取总记录数
$count_query = "SELECT COUNT(*) as total FROM audit_log $where_sql";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);

// 获取审计日志记录
$query = "SELECT * FROM audit_log $where_sql ORDER BY action_timestamp DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

// 获取统计数据
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admin_actions,
    SUM(CASE WHEN user_type = 'doctor' THEN 1 ELSE 0 END) as doctor_actions,
    SUM(CASE WHEN action_type = 'INSERT' THEN 1 ELSE 0 END) as inserts,
    SUM(CASE WHEN action_type = 'UPDATE' THEN 1 ELSE 0 END) as updates,
    SUM(CASE WHEN action_type = 'DELETE' THEN 1 ELSE 0 END) as deletes,
    SUM(CASE WHEN action_type = 'SELECT' THEN 1 ELSE 0 END) as selects,
    SUM(CASE WHEN action_type = 'LOGIN' THEN 1 ELSE 0 END) as logins
    FROM audit_log";
$stats = $conn->query($stats_query)->fetch_assoc();

// 获取唯一的表名列表
$tables_query = "SELECT DISTINCT table_name FROM audit_log WHERE table_name IS NOT NULL ORDER BY table_name";
$tables_result = $conn->query($tables_query);
$tables = [];
while ($row = $tables_result->fetch_assoc()) {
    $tables[] = $row['table_name'];
}

// 获取唯一的用户名列表
$users_query = "SELECT DISTINCT username FROM audit_log ORDER BY username";
$users_result = $conn->query($users_query);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row['username'];
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Language" content="en">
    <title>Audit Log - Admin</title>
    <!-- Flatpickr CSS for English date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .back-link {
            padding: 12px 24px;
            background: white;
            color: #f5576c;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: 2px solid #f5576c;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: #f5576c;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #f5576c;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filters-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .filters-title {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: #666;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        /* Flatpickr自定义样式 */
        .filter-group input[readonly] {
            background-color: white;
            cursor: pointer;
        }

        .flatpickr-calendar {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #f5576c;
            outline: none;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h2 {
            color: #333;
        }

        .records-count {
            color: #666;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        table th {
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        table td {
            padding: 12px 10px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .user-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .user-admin {
            background: #ffc107;
            color: #856404;
        }

        .user-doctor {
            background: #17a2b8;
            color: white;
        }

        .user-system {
            background: #6c757d;
            color: white;
        }

        .action-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .action-INSERT {
            background: #d4edda;
            color: #155724;
        }

        .action-UPDATE {
            background: #cce5ff;
            color: #004085;
        }

        .action-DELETE {
            background: #f8d7da;
            color: #721c24;
        }

        .action-SELECT {
            background: #d1ecf1;
            color: #0c5460;
        }

        .action-LOGIN {
            background: #d4edda;
            color: #155724;
        }

        .action-LOGOUT {
            background: #f8d7da;
            color: #721c24;
        }

        .description-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }

        .page-btn {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            font-weight: 600;
        }

        .page-btn:hover {
            border-color: #f5576c;
            color: #f5576c;
        }

        .page-btn.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-color: transparent;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .view-details-btn {
            padding: 6px 12px;
            background: #f5576c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .view-details-btn:hover {
            background: #f093fb;
        }

        /* Modal for details */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .modal-header h2 {
            color: #333;
        }

        .close-btn {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            word-break: break-word;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            table th,
            table td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📊 Audit Log - System Activity Records</h1>
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['admin_actions']); ?></div>
            <div class="stat-label">Admin Actions</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['doctor_actions']); ?></div>
            <div class="stat-label">Doctor Actions</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['inserts']); ?></div>
            <div class="stat-label">Inserts</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['updates']); ?></div>
            <div class="stat-label">Updates</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['deletes']); ?></div>
            <div class="stat-label">Deletes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($stats['logins']); ?></div>
            <div class="stat-label">Logins</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-panel">
        <div class="filters-title">🔍 Filter Audit Records</div>
        <form method="get" action="">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>User Type</label>
                    <select name="user_type">
                        <option value="all" <?php echo $filter_user_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="admin" <?php echo $filter_user_type === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="doctor" <?php echo $filter_user_type === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                        <option value="system" <?php echo $filter_user_type === 'system' ? 'selected' : ''; ?>>System</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Action Type</label>
                    <select name="action_type">
                        <option value="all" <?php echo $filter_action_type === 'all' ? 'selected' : ''; ?>>All Actions</option>
                        <option value="INSERT" <?php echo $filter_action_type === 'INSERT' ? 'selected' : ''; ?>>INSERT</option>
                        <option value="UPDATE" <?php echo $filter_action_type === 'UPDATE' ? 'selected' : ''; ?>>UPDATE</option>
                        <option value="DELETE" <?php echo $filter_action_type === 'DELETE' ? 'selected' : ''; ?>>DELETE</option>
                        <option value="SELECT" <?php echo $filter_action_type === 'SELECT' ? 'selected' : ''; ?>>SELECT</option>
                        <option value="LOGIN" <?php echo $filter_action_type === 'LOGIN' ? 'selected' : ''; ?>>LOGIN</option>
                        <option value="LOGOUT" <?php echo $filter_action_type === 'LOGOUT' ? 'selected' : ''; ?>>LOGOUT</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Search username..." value="<?php echo htmlspecialchars($filter_username); ?>">
                </div>

                <div class="filter-group">
                    <label>Table Name</label>
                    <select name="table">
                        <option value="all" <?php echo $filter_table === 'all' ? 'selected' : ''; ?>>All Tables</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo htmlspecialchars($table); ?>" <?php echo $filter_table === $table ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($table); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Date From</label>
                    <input type="text" name="date_from" id="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" placeholder="YYYY-MM-DD" readonly>
                </div>

                <div class="filter-group">
                    <label>Date To</label>
                    <input type="text" name="date_to" id="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" placeholder="YYYY-MM-DD" readonly>
                </div>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                <a href="audit_log.php" class="btn btn-secondary">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- Audit Log Table -->
    <div class="content">
        <div class="table-header">
            <h2>Audit Records</h2>
            <div class="records-count">Showing <?php echo count($logs); ?> of <?php echo number_format($total_records); ?> records</div>
        </div>

        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No audit records found</h3>
                <p>Try adjusting your filters to see more results.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User Type</th>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Description</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['log_id']; ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['action_timestamp'])); ?></td>
                            <td>
                                <span class="user-badge user-<?php echo $log['user_type']; ?>">
                                    <?php echo strtoupper($log['user_type']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                            <td>
                                <span class="action-badge action-<?php echo $log['action_type']; ?>">
                                    <?php echo $log['action_type']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                            <td>
                                <div class="description-cell" title="<?php echo htmlspecialchars($log['description']); ?>">
                                    <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                                </div>
                            </td>
                            <td>
                                <button class="view-details-btn" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-btn">← Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" 
                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-btn">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Audit Record Details</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

<script>
function viewDetails(log) {
    const modal = document.getElementById('detailsModal');
    const modalBody = document.getElementById('modalBody');
    
    modalBody.innerHTML = `
        <div class="detail-row">
            <div class="detail-label">Log ID:</div>
            <div class="detail-value">${log.log_id}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Timestamp:</div>
            <div class="detail-value">${new Date(log.action_timestamp).toLocaleString()}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">User Type:</div>
            <div class="detail-value"><span class="user-badge user-${log.user_type}">${log.user_type.toUpperCase()}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Username:</div>
            <div class="detail-value"><strong>${log.username}</strong></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Action Type:</div>
            <div class="detail-value"><span class="action-badge action-${log.action_type}">${log.action_type}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Table Name:</div>
            <div class="detail-value">${log.table_name || '-'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Record ID:</div>
            <div class="detail-value">${log.record_id || '-'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">IP Address:</div>
            <div class="detail-value">${log.ip_address || '-'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Description:</div>
            <div class="detail-value">${log.description || '-'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Old Value:</div>
            <div class="detail-value" style="max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                ${log.old_value || '-'}
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">New Value:</div>
            <div class="detail-value" style="max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                ${log.new_value || '-'}
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

</body>
</html>

<!-- Flatpickr JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// 初始化英文日期选择器
document.addEventListener('DOMContentLoaded', function() {
    // 配置选项
    const config = {
        dateFormat: "Y-m-d",
        locale: "en",
        allowInput: true,
        clickOpens: true
    };
    
    // 初始化Date From
    if (document.getElementById('date_from')) {
        flatpickr("#date_from", config);
    }
    
    // 初始化Date To
    if (document.getElementById('date_to')) {
        flatpickr("#date_to", config);
    }
});
</script>

<?php $conn->close(); ?>