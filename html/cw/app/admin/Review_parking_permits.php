<?php
session_start();
require_once '../includes/db.php';

// 检查管理员是否已登录
if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin"];
$msg = "";
$msg_type = "";

// 处理审批操作
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $permit_id = intval($_POST['permit_id']);
    $action = $_POST['action'];
    $comment = trim($_POST['comment'] ?? '');
    $permit_number = trim($_POST['permit_number'] ?? '');
    
    if ($action === 'approve') {
        // 批准 - 必须填写许可证号
        if (empty($permit_number)) {
            $msg = "Permit number is required for approval!";
            $msg_type = "error";
        } else {
            // 检查许可证号是否已存在
            $check_stmt = $conn->prepare("SELECT permit_id FROM parking_permits WHERE permit_number = ? AND permit_id != ?");
            $check_stmt->bind_param("si", $permit_number, $permit_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $msg = "Permit number already exists!";
                $msg_type = "error";
            } else {
                $update_stmt = $conn->prepare("UPDATE parking_permits SET status = 'approved', permit_number = ?, admin_comment = ?, approved_by = ?, approval_date = NOW() WHERE permit_id = ?");
                $update_stmt->bind_param("sssi", $permit_number, $comment, $admin_name, $permit_id);
                if ($update_stmt->execute()) {
                    $msg = "Parking permit approved successfully! Permit Number: " . htmlspecialchars($permit_number);
                    $msg_type = "success";
                    
                    // 记录审计日志
                    $audit_stmt = $conn->prepare("INSERT INTO audit_log (user_type, username, action_type, table_name, record_id, description) VALUES ('admin', ?, 'UPDATE', 'parking_permits', ?, ?)");
                    $desc = "Approved parking permit #$permit_id with permit number $permit_number";
                    $audit_stmt->bind_param("sis", $admin_name, $permit_id, $desc);
                    $audit_stmt->execute();
                } else {
                    $msg = "Error approving permit.";
                    $msg_type = "error";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    } elseif ($action === 'reject') {
        // 拒绝 - 必须填写拒绝理由
        if (empty($comment)) {
            $msg = "Rejection reason is required!";
            $msg_type = "error";
        } else {
            $update_stmt = $conn->prepare("UPDATE parking_permits SET status = 'rejected', admin_comment = ?, approved_by = ?, approval_date = NOW() WHERE permit_id = ?");
            $update_stmt->bind_param("ssi", $comment, $admin_name, $permit_id);
            if ($update_stmt->execute()) {
                $msg = "Parking permit rejected.";
                $msg_type = "success";
                
                // 记录审计日志
                $audit_stmt = $conn->prepare("INSERT INTO audit_log (user_type, username, action_type, table_name, record_id, description) VALUES ('admin', ?, 'UPDATE', 'parking_permits', ?, ?)");
                $desc = "Rejected parking permit #$permit_id - Reason: $comment";
                $audit_stmt->bind_param("sis", $admin_name, $permit_id, $desc);
                $audit_stmt->execute();
            } else {
                $msg = "Error rejecting permit.";
                $msg_type = "error";
            }
            $update_stmt->close();
        }
    }
}

// 获取统计数据
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM parking_permits";
$stats_result = $conn->query($stats_query);
if ($stats_result && $stats_result->num_rows > 0) {
    $stats = $stats_result->fetch_assoc();
}

// 获取筛选状态
$filter = $_GET['filter'] ?? 'all';

// 获取所有停车许可申请
$permits = [];
$query = "SELECT pp.*, d.firstname, d.lastname 
          FROM parking_permits pp 
          JOIN doctor d ON pp.doctor_id = d.staffno";

if ($filter !== 'all') {
    $query .= " WHERE pp.status = ?";
}

$query .= " ORDER BY pp.application_date DESC";

if ($filter !== 'all') {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $permits[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Parking Permits - Admin</title>
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
            max-width: 1400px;
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 36px;
            font-weight: bold;
            color: #f5576c;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #666;
            font-weight: 600;
        }

        .filter-btn:hover {
            border-color: #f5576c;
            color: #f5576c;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-color: transparent;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-review {
            padding: 8px 16px;
            background: #f5576c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-review:hover {
            background: #f093fb;
        }

        .btn-review:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Modal */
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
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .modal-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .modal-info p {
            margin: 8px 0;
            color: #666;
        }

        .modal-info strong {
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn-action {
            flex: 1;
            padding: 12px;
            border: 2px solid;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-approve {
            background: white;
            color: #28a745;
            border-color: #28a745;
        }

        .btn-approve.active {
            background: #28a745;
            color: white;
        }

        .btn-reject {
            background: white;
            color: #dc3545;
            border-color: #dc3545;
        }

        .btn-reject.active {
            background: #dc3545;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #f5576c;
            outline: none;
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            table {
                font-size: 14px;
            }

            table th,
            table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📋 Review Parking Permits</h1>
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo htmlspecialchars($msg_type); ?>">
            <span><?php echo $msg_type === 'error' ? '⚠️' : '✓'; ?></span>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['rejected']; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="?filter=approved" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
        <a href="?filter=rejected" class="filter-btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
    </div>

    <!-- Applications Table -->
    <div class="content">
        <h2>Parking Permit Applications</h2>
        
        <?php if (empty($permits)): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No applications found</h3>
                <p>There are no parking permit applications to review.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Vehicle</th>
                        <th>License Plate</th>
                        <th>Plan</th>
                        <th>Cost</th>
                        <th>Permit Number</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permits as $permit): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($permit['application_date'])); ?></td>
                            <td><?php echo htmlspecialchars($permit['firstname'] . ' ' . $permit['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($permit['vehicle_make'] . ' ' . $permit['vehicle_model']); ?></td>
                            <td><strong><?php echo htmlspecialchars($permit['license_plate']); ?></strong></td>
                            <td><?php echo ucfirst($permit['parking_plan']); ?></td>
                            <td>£<?php echo number_format($permit['plan_cost'], 2); ?></td>
                            <td>
                                <?php if (!empty($permit['permit_number'])): ?>
                                    <strong style="color: #28a745;"><?php echo htmlspecialchars($permit['permit_number']); ?></strong>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $permit['status']; ?>">
                                    <?php echo ucfirst($permit['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($permit['status'] === 'pending'): ?>
                                    <button class="btn-review" onclick="openReviewModal(<?php echo htmlspecialchars(json_encode($permit)); ?>)">Review</button>
                                <?php else: ?>
                                    <button class="btn-review" disabled>Reviewed</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Review Parking Permit Application</h2>
        </div>
        
        <div class="modal-info" id="modalInfo"></div>
        
        <form method="post" id="reviewForm">
            <input type="hidden" name="permit_id" id="permitId">
            <input type="hidden" name="action" id="actionType">
            
            <div class="action-buttons">
                <button type="button" class="btn-action btn-approve" id="btnApprove" onclick="selectAction('approve')">
                    ✓ Approve
                </button>
                <button type="button" class="btn-action btn-reject" id="btnReject" onclick="selectAction('reject')">
                    ✗ Reject
                </button>
            </div>
            
            <div id="permitNumberField" class="form-group" style="display: none;">
                <label for="permit_number">Permit Number * (Required for approval)</label>
                <input type="text" name="permit_number" id="permit_number" placeholder="e.g., PKG-2024-001" style="text-transform: uppercase;">
            </div>
            
            <div class="form-group">
                <label for="comment" id="commentLabel">Comment/Reason</label>
                <textarea name="comment" id="comment" rows="4" placeholder="Enter your comment or reason..."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedAction = null;

function openReviewModal(permit) {
    const modal = document.getElementById('reviewModal');
    const modalInfo = document.getElementById('modalInfo');
    
    modalInfo.innerHTML = `
        <p><strong>Doctor:</strong> ${permit.firstname} ${permit.lastname}</p>
        <p><strong>Vehicle:</strong> ${permit.vehicle_make} ${permit.vehicle_model} (${permit.vehicle_color})</p>
        <p><strong>License Plate:</strong> ${permit.license_plate}</p>
        <p><strong>Plan:</strong> ${permit.parking_plan.charAt(0).toUpperCase() + permit.parking_plan.slice(1)} (£${parseFloat(permit.plan_cost).toFixed(2)})</p>
        <p><strong>Application Date:</strong> ${new Date(permit.application_date).toLocaleDateString()}</p>
    `;
    
    document.getElementById('permitId').value = permit.permit_id;
    document.getElementById('reviewForm').reset();
    document.getElementById('permitId').value = permit.permit_id;
    
    // Reset buttons
    document.getElementById('btnApprove').classList.remove('active');
    document.getElementById('btnReject').classList.remove('active');
    document.getElementById('permitNumberField').style.display = 'none';
    document.getElementById('actionType').value = '';
    selectedAction = null;
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

function selectAction(action) {
    selectedAction = action;
    document.getElementById('actionType').value = action;
    
    const btnApprove = document.getElementById('btnApprove');
    const btnReject = document.getElementById('btnReject');
    const permitNumberField = document.getElementById('permitNumberField');
    const commentLabel = document.getElementById('commentLabel');
    const comment = document.getElementById('comment');
    
    if (action === 'approve') {
        btnApprove.classList.add('active');
        btnReject.classList.remove('active');
        permitNumberField.style.display = 'block';
        commentLabel.textContent = 'Comment (Optional)';
        comment.placeholder = 'Enter any additional comments...';
        document.getElementById('permit_number').required = true;
    } else {
        btnReject.classList.add('active');
        btnApprove.classList.remove('active');
        permitNumberField.style.display = 'none';
        commentLabel.textContent = 'Rejection Reason * (Required)';
        comment.placeholder = 'Please enter the reason for rejection...';
        document.getElementById('permit_number').required = false;
        comment.required = true;
    }
}

// Auto-uppercase permit number
document.getElementById('permit_number').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reviewModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Form validation
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    if (!selectedAction) {
        e.preventDefault();
        alert('Please select Approve or Reject');
        return false;
    }
    
    if (selectedAction === 'approve') {
        const permitNumber = document.getElementById('permit_number').value.trim();
        if (!permitNumber) {
            e.preventDefault();
            alert('Permit number is required for approval');
            return false;
        }
    }
    
    if (selectedAction === 'reject') {
        const comment = document.getElementById('comment').value.trim();
        if (!comment) {
            e.preventDefault();
            alert('Rejection reason is required');
            return false;
        }
    }
    
    return confirm('Are you sure you want to ' + selectedAction + ' this application?');
});
</script>

</body>
</html>

<?php $conn->close(); ?>