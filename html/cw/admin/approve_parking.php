<?php
require_once '../config/db.inc.php';
require_once '../includes/auth_check.php';

// 仅管理员可访问
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: /cw/doctor_dashboard.php');
    exit;
}

$pdo = getDBConnection();

// 处理审批/拒绝请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if (!$requestId || !$action) {
        header('Location: approve_parking.php?error=missing_data');
        exit;
    }
    
    // 获取请求信息
    $checkStmt = $pdo->prepare("SELECT * FROM parking_requests WHERE request_id = ?");
    $checkStmt->execute([$requestId]);
    $request = $checkStmt->fetch();
    
    if (!$request || $request['status'] !== 'pending') {
        header('Location: approve_parking.php?error=invalid_request');
        exit;
    }
    
    if ($action === 'approve') {
        // 生成唯一的停车许可号码
        $permitNumber = 'PERMIT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        
        // 计算许可期限
        $permitType = $request['permit_type'];
        $startDate = date('Y-m-d');
        
        if ($permitType === 'monthly') {
            $endDate = date('Y-m-d', strtotime('+1 month'));
        } else {
            $endDate = date('Y-m-d', strtotime('+1 year'));
        }
        
        // 更新请求状态为已批准
        $stmt = $pdo->prepare("
            UPDATE parking_requests 
            SET status = 'approved', 
                permit_number = ?,
                processed_by = ?,
                processed_date = NOW(),
                permit_start_date = ?,
                permit_end_date = ?,
                rejection_reason = NULL  -- 清除拒绝原因
            WHERE request_id = ?
        ");
        $stmt->execute([$permitNumber, $_SESSION['user_id'], $startDate, $endDate, $requestId]);
        
        // 记录审计日志
        logAudit('APPROVE', 'parking_requests', $requestId, 'pending', json_encode([
            'permit_number' => $permitNumber,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]));
        
    } elseif ($action === 'reject') {
        // 获取拒绝原因
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        
        if (empty($rejectionReason)) {
            header('Location: approve_parking.php?error=missing_reason');
            exit;
        }
        
        // 更新请求状态为已拒绝
        $stmt = $pdo->prepare("
            UPDATE parking_requests 
            SET status = 'rejected', 
                processed_by = ?,
                processed_date = NOW(),
                rejection_reason = ?
            WHERE request_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $rejectionReason, $requestId]);
        
        // 记录审计日志
        logAudit('REJECT', 'parking_requests', $requestId, 'pending', json_encode([
            'rejection_reason' => $rejectionReason
        ]));
    }
    
    header('Location: approve_parking.php?success=true');
    exit;
}

// 获取所有待处理请求
$stmt = $pdo->prepare("
    SELECT pr.*, d.FirstName, d.LastName, d.Staff_no
    FROM parking_requests pr
    JOIN DOCTOR d ON pr.doctor_id = d.Doctor_id
    WHERE pr.status = 'pending'
    ORDER BY pr.request_date
");
$stmt->execute();
$pendingRequests = $stmt->fetchAll();

// 显示消息
$message = '';
if (isset($_GET['error'])) {
    $errors = [
        'invalid_request' => 'The selected request is no longer valid.',
        'missing_data' => 'Missing required data.',
        'missing_reason' => 'Rejection reason is required.'
    ];
    $message = '<div class="message error">' . ($errors[$_GET['error']] ?? 'An error occurred') . '</div>';
} elseif (isset($_GET['success'])) {
    $message = '<div class="message success">Request processed successfully!</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Parking Requests - QMC Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Parking Permit Requests</h1>
        
        <?php echo $message; ?>

        <?php if (empty($pendingRequests)): ?>
            <!-- 无待处理请求 -->
            <div class="card">
                <div class="empty-message">
                    No pending parking requests
                </div>
            </div>
        <?php else: ?>
            <!-- 待处理请求表格 -->
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Staff No.</th>
                            <th>Car Registration</th>
                            <th>Permit Type</th>
                            <th>Fee</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $request): ?>
                        <tr>
                            <td>Dr. <?php echo htmlspecialchars($request['FirstName'] . ' ' . $request['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($request['Staff_no']); ?></td>
                            <td><?php echo htmlspecialchars($request['car_registration']); ?></td>
                            <td><?php echo ucfirst($request['permit_type']); ?></td>
                            <td>£<?php echo number_format($request['fee'], 2); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- 批准按钮 -->
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-success btn-small">
                                            Approve
                                        </button>
                                    </form>
                                    
                                    <!-- 拒绝按钮（带原因输入） -->
                                    <button type="button" class="btn-danger btn-small" onclick="showRejectForm(<?php echo $request['request_id']; ?>)">
                                        Reject
                                    </button>
                                    
                                    <!-- 拒绝原因表单（隐藏） -->
                                    <form method="POST" id="reject-form-<?php echo $request['request_id']; ?>" class="hidden" style="display: inline-block; margin-left: 8px;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="rejection_reason" placeholder="Reason for rejection" required 
                                               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; min-width: 200px;">
                                        <button type="submit" class="btn-danger btn-small">Confirm Reject</button>
                                        <button type="button" class="btn-secondary btn-small" onclick="hideRejectForm(<?php echo $request['request_id']; ?>)">Cancel</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- 返回仪表板按钮 -->
        <div class="mt-30 text-center">
            <a href="/cw/admin_dashboard.php" class="btn btn-secondary btn-medium">Back to Dashboard</a>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // 显示拒绝原因表单
        function showRejectForm(requestId) {
            // 隐藏所有其他拒绝表单
            document.querySelectorAll('[id^="reject-form-"]').forEach(form => {
                form.style.display = 'none';
            });
            
            // 显示当前请求的拒绝表单
            const rejectForm = document.getElementById('reject-form-' + requestId);
            if (rejectForm) {
                rejectForm.style.display = 'inline-block';
                // 聚焦到原因输入框
                rejectForm.querySelector('input[name="rejection_reason"]').focus();
            }
        }
        
        // 隐藏拒绝原因表单
        function hideRejectForm(requestId) {
            const rejectForm = document.getElementById('reject-form-' + requestId);
            if (rejectForm) {
                rejectForm.style.display = 'none';
                // 清空输入框
                rejectForm.querySelector('input[name="rejection_reason"]').value = '';
            }
        }
        
        // 处理拒绝表单提交验证
        document.querySelectorAll('[id^="reject-form-"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const reasonInput = this.querySelector('input[name="rejection_reason"]');
                if (!reasonInput.value.trim()) {
                    e.preventDefault();
                    alert('Please provide a reason for rejection.');
                    reasonInput.focus();
                }
            });
        });
    </script>
</body>
</html>