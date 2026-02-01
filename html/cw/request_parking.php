<?php
require_once 'config/db.inc.php';
require_once 'includes/auth_check.php';

$pdo = getDBConnection();
$message = '';
$currentRequest = null;
$canSubmitNewRequest = false;

// 获取医生ID - 直接使用会话中存储的doctor_id
$doctorId = $_SESSION['doctor_id'] ?? 0;

if ($doctorId === 0) {
    // 如果无法获取医生ID，显示错误
    $message = '<div class="message error">Doctor ID not found. Please contact administrator.</div>';
    $canSubmitNewRequest = false;
} else {
    // 获取医生最新的停车请求
    $stmt = $pdo->prepare("
        SELECT * FROM parking_requests 
        WHERE doctor_id = ? 
        ORDER BY request_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$doctorId]);
    $currentRequest = $stmt->fetch();

    // 检查是否可以提交新申请
    if (!$currentRequest) {
        // 没有申请记录，可以提交
        $canSubmitNewRequest = true;
    } else {
        $status = $currentRequest['status'];
        
        // 检查许可是否已过期
        if ($status === 'approved' && $currentRequest['permit_end_date']) {
            if (strtotime($currentRequest['permit_end_date']) < time()) {
                // 许可已过期，可以提交新申请
                $canSubmitNewRequest = true;
            }
        } elseif ($status === 'rejected' || $status === 'expired') {
            // 被拒绝或已过期，可以提交新申请
            $canSubmitNewRequest = true;
        }
        // pending和approved(未过期)状态不能提交新申请
    }
}

/**
 * 计算停车许可费用
 * @param string $type 许可类型（monthly/yearly）
 * @return float 费用金额
 */
function calculateFee($type) {
    return $type === 'monthly' ? 50.00 : 500.00;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canSubmitNewRequest) {
    $permitType = $_POST['permit_type'] ?? 'monthly';
    $carReg = trim($_POST['car_registration'] ?? '');
    $fee = calculateFee($permitType);
    
    if (empty($carReg)) {
        $message = '<div class="message error">Car registration is required.</div>';
    } else {
        try {
            // 插入新的停车请求
            $stmt = $pdo->prepare("
                INSERT INTO parking_requests 
                (doctor_id, car_registration, permit_type, fee, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([$doctorId, $carReg, $permitType, $fee]);
            $requestId = $pdo->lastInsertId();
            
            // 记录审计日志
            logAudit('CREATE', 'parking_requests', $requestId, null, json_encode([
                'doctor_id' => $doctorId,
                'car_reg' => $carReg,
                'permit_type' => $permitType,
                'fee' => $fee
            ]));
            
            $message = '<div class="message success">Parking permit request submitted successfully!</div>';
            
            // 刷新当前请求
            $stmt = $pdo->prepare("SELECT * FROM parking_requests WHERE request_id = ?");
            $stmt->execute([$requestId]);
            $currentRequest = $stmt->fetch();
            $canSubmitNewRequest = false; // 提交后不能再提交
            
        } catch (Exception $e) {
            $message = '<div class="message error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Permit Request - QMC</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Parking Permit Request</h1>
        
        <?php echo $message; ?>
        
        <!-- 显示当前请求状态 -->
        <?php if ($currentRequest): ?>
            <div class="card">
                <h2>Current Request Status</h2>
                
                <div class="details-grid">
                    <div class="detail-box">
                        <span class="detail-label">Status</span>
                        <span class="detail-value status-<?php echo $currentRequest['status']; ?>">
                            <?php echo ucfirst($currentRequest['status']); ?>
                        </span>
                    </div>
                    
                    <div class="detail-box">
                        <span class="detail-label">Car Registration</span>
                        <span class="detail-value"><?php echo htmlspecialchars($currentRequest['car_registration']); ?></span>
                    </div>
                    
                    <div class="detail-box">
                        <span class="detail-label">Permit Type</span>
                        <span class="detail-value"><?php echo ucfirst($currentRequest['permit_type']); ?></span>
                    </div>
                    
                    <div class="detail-box">
                        <span class="detail-label">Fee</span>
                        <span class="detail-value">£<?php echo number_format($currentRequest['fee'], 2); ?></span>
                    </div>
                    
                    <div class="detail-box">
                        <span class="detail-label">Request Date</span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($currentRequest['request_date'])); ?></span>
                    </div>
                    
                    <?php if ($currentRequest['permit_start_date']): ?>
                        <div class="detail-box">
                            <span class="detail-label">Permit Start</span>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($currentRequest['permit_start_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($currentRequest['permit_end_date']): ?>
                        <div class="detail-box">
                            <span class="detail-label">Permit End</span>
                            <span class="detail-value"><?php echo date('d/m/Y', strtotime($currentRequest['permit_end_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($currentRequest['status'] === 'rejected' && !empty($currentRequest['rejection_reason'])): ?>
                        <div class="detail-box" style="grid-column: 1 / -1;">
                            <span class="detail-label">Rejection Reason</span>
                            <span class="detail-value"><?php echo htmlspecialchars($currentRequest['rejection_reason']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 显示许可号码（如果已批准） -->
                <?php if ($currentRequest['status'] === 'approved' && $currentRequest['permit_number']): ?>
                    <div class="permit-number">
                        <span class="permit-number-label">Your Parking Permit Number:</span>
                        <span class="permit-number-value"><?php echo htmlspecialchars($currentRequest['permit_number']); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- 状态说明 -->
                <?php if ($currentRequest['status'] === 'approved'): ?>
                    <?php if ($currentRequest['permit_end_date'] && strtotime($currentRequest['permit_end_date']) < time()): ?>
                        <div class="card mt-15">
                            <p><strong>Note:</strong> Your parking permit has expired. You can now submit a new request.</p>
                        </div>
                    <?php else: ?>
                        <div class="card mt-15">
                            <p><strong>Note:</strong> Your parking permit is active. You cannot submit a new request while you have an active permit.</p>
                        </div>
                    <?php endif; ?>
                <?php elseif ($currentRequest['status'] === 'pending'): ?>
                    <div class="card mt-15">
                        <p><strong>Note:</strong> Your request is pending approval by administration. You cannot submit a new request while you have a pending request.</p>
                    </div>
                <?php elseif ($currentRequest['status'] === 'rejected'): ?>
                    <div class="card mt-15">
                        <p><strong>Note:</strong> Your request was rejected. 
                        <?php if (!empty($currentRequest['rejection_reason'])): ?>
                            Reason: <?php echo htmlspecialchars($currentRequest['rejection_reason']); ?>
                        <?php endif; ?>
                        You can now submit a new request below.</p>
                    </div>
                <?php elseif ($currentRequest['status'] === 'expired'): ?>
                    <div class="card mt-15">
                        <p><strong>Note:</strong> Your parking permit has expired. You can now submit a new request below.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 提交新申请表单 -->
        <?php if ($canSubmitNewRequest): ?>
            <div class="card">
                <form method="POST">
                    <div class="form-section">
                        <h3>Permit Details</h3>
                        
                        <div class="form-group">
                            <label>Car Registration *</label>
                            <input type="text" name="car_registration" required
                                   placeholder="e.g., AB12 CDE" 
                                   value="<?php echo htmlspecialchars($_POST['car_registration'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Permit Type *</label>
                            <div class="details-grid">
                                <div class="detail-box">
                                    <label>
                                        <input type="radio" name="permit_type" value="monthly" checked required> 
                                        Monthly Permit (£50.00)
                                    </label>
                                </div>
                                <div class="detail-box">
                                    <label>
                                        <input type="radio" name="permit_type" value="yearly" required> 
                                        Yearly Permit (£500.00)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="fee-display">
                        <h3>Monthly Fee: <span id="feeAmount">£50.00</span></h3>
                        <p>Fee will be charged upon approval by administration</p>
                    </div>
            
                    <div class="card mt-15">
                        <p><strong>Important:</strong></p>
                        <ul class="note-list-ul">
                            <li>You can only have one pending or active parking permit at a time</li>
                            <li>Once submitted, you cannot submit another request until this one is processed</li>
                            <li>If approved, you will receive a unique parking permit number</li>
                            <li>If rejected, you will receive a reason and can submit a new request</li>
                        </ul>
                    </div>
            
                    <div class="mt-30 text-center">
                        <button type="submit" class="btn btn-primary btn-medium">
                            Submit New Request
                        </button>
                        <a href="/cw/doctor_dashboard.php" class="btn btn-secondary btn-medium">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
            
        <script>
            // 更新费用显示
            document.querySelectorAll('input[name="permit_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const fee = this.value === 'monthly' ? 50.00 : 500.00;
                    const text = this.value === 'monthly' ? 'Monthly' : 'Yearly';
                    document.getElementById('feeAmount').textContent = '£' + fee.toFixed(2);
                    document.querySelector('.fee-display h3').textContent = text + ' Fee: £' + fee.toFixed(2);
                });
            });
        </script>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>