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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staffno = trim($_POST['staffno']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $specialisation = intval($_POST['specialisation']);
    $qualification = trim($_POST['qualification']);
    $pay = floatval($_POST['pay']);
    $gender = intval($_POST['gender']);
    $consultantstatus = intval($_POST['consultantstatus']);
    $address = trim($_POST['address']);
    
    // 验证必填字段
    if (empty($staffno) || empty($firstname) || empty($pay)) {
        $msg = "Staff Number, First Name, and Pay are required!";
        $msg_type = "error";
    } else {
        // 检查Staff Number是否已存在
        $check_stmt = $conn->prepare("SELECT staffno FROM doctor WHERE staffno = ?");
        $check_stmt->bind_param("s", $staffno);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $msg = "Staff Number already exists!";
            $msg_type = "error";
        } else {
            // 插入新医生
            $insert_stmt = $conn->prepare("INSERT INTO doctor (staffno, firstname, lastname, specialisation, qualification, pay, gender, consultantstatus, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssisdiii", $staffno, $firstname, $lastname, $specialisation, $qualification, $pay, $gender, $consultantstatus, $address);
            
            if ($insert_stmt->execute()) {
                $msg = "Doctor account created successfully! Staff Number: " . htmlspecialchars($staffno);
                $msg_type = "success";
                
                // 记录审计日志
                $audit_stmt = $conn->prepare("INSERT INTO audit_log (user_type, username, action_type, table_name, record_id, description) VALUES ('admin', ?, 'INSERT', 'doctor', ?, ?)");
                $desc = "Created new doctor account: $firstname $lastname (Staff No: $staffno)";
                $audit_stmt->bind_param("sss", $admin_name, $staffno, $desc);
                $audit_stmt->execute();
                
                // 清空表单
                $_POST = [];
            } else {
                $msg = "Error creating doctor account: " . $conn->error;
                $msg_type = "error";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor - Admin</title>
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
            max-width: 1000px;
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

        .content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #f5576c;
            outline: none;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .helper-text {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 10px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
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

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .info-box h3 {
            color: #1976D2;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #0d47a1;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>➕ Add New Doctor</h1>
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo htmlspecialchars($msg_type); ?>">
            <span><?php echo $msg_type === 'error' ? '⚠️' : '✓'; ?></span>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="content">
        <h2 class="form-title">Register New Doctor Account</h2>
        <p class="form-subtitle">Fill in the details below to create a new doctor account in the system.</p>

        <div class="info-box">
            <h3>ℹ️ Important Information</h3>
            <p>Fields marked with <span class="required">*</span> are required. Staff Number must be unique and cannot be changed later.</p>
        </div>

        <form method="post" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="staffno">Staff Number <span class="required">*</span></label>
                    <input type="text" id="staffno" name="staffno" required 
                           value="<?php echo isset($_POST['staffno']) ? htmlspecialchars($_POST['staffno']) : ''; ?>"
                           placeholder="e.g., DOC001">
                    <span class="helper-text">Unique identifier for the doctor</span>
                </div>

                <div class="form-group">
                    <label for="firstname">First Name <span class="required">*</span></label>
                    <input type="text" id="firstname" name="firstname" required
                           value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"
                           placeholder="e.g., John">
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname"
                           value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"
                           placeholder="e.g., Smith">
                </div>

                <div class="form-group">
                    <label for="specialisation">Specialisation</label>
                    <select id="specialisation" name="specialisation">
                        <option value="0">General Practice</option>
                        <option value="1">Cardiology</option>
                        <option value="2">Neurology</option>
                        <option value="3">Pediatrics</option>
                        <option value="4">Orthopedics</option>
                        <option value="5">Dermatology</option>
                        <option value="6">Psychiatry</option>
                        <option value="7">Radiology</option>
                        <option value="8">Emergency Medicine</option>
                        <option value="9">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="qualification">Qualification</label>
                    <input type="text" id="qualification" name="qualification"
                           value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>"
                           placeholder="e.g., MD, MBBS, DO">
                    <span class="helper-text">Medical degrees and certifications</span>
                </div>

                <div class="form-group">
                    <label for="pay">Annual Salary (£) <span class="required">*</span></label>
                    <input type="number" id="pay" name="pay" step="0.01" min="0" required
                           value="<?php echo isset($_POST['pay']) ? htmlspecialchars($_POST['pay']) : ''; ?>"
                           placeholder="e.g., 55000">
                </div>

                <div class="form-group">
                    <label for="gender">Gender <span class="required">*</span></label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="0" <?php echo (isset($_POST['gender']) && $_POST['gender'] == '0') ? 'selected' : ''; ?>>Male</option>
                        <option value="1" <?php echo (isset($_POST['gender']) && $_POST['gender'] == '1') ? 'selected' : ''; ?>>Female</option>
                        <option value="2" <?php echo (isset($_POST['gender']) && $_POST['gender'] == '2') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="consultantstatus">Consultant Status <span class="required">*</span></label>
                    <select id="consultantstatus" name="consultantstatus" required>
                        <option value="">Select Status</option>
                        <option value="1" <?php echo (isset($_POST['consultantstatus']) && $_POST['consultantstatus'] == '1') ? 'selected' : ''; ?>>Yes - Consultant</option>
                        <option value="0" <?php echo (isset($_POST['consultantstatus']) && $_POST['consultantstatus'] == '0') ? 'selected' : ''; ?>>No - Junior Doctor</option>
                    </select>
                    <span class="helper-text">Senior position status</span>
                </div>

                <div class="form-group full-width">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Enter doctor's address..."><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">🔄 Reset Form</button>
                    <button type="submit" class="btn btn-primary">✓ Create Doctor Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-uppercase staff number
document.getElementById('staffno').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const staffno = document.getElementById('staffno').value.trim();
    const firstname = document.getElementById('firstname').value.trim();
    const pay = document.getElementById('pay').value;
    
    if (!staffno || !firstname || !pay) {
        e.preventDefault();
        alert('Please fill in all required fields (marked with *)');
        return false;
    }
    
    if (parseFloat(pay) <= 0) {
        e.preventDefault();
        alert('Annual salary must be greater than 0');
        return false;
    }
    
    return confirm('Are you sure you want to create this doctor account?');
});
</script>

</body>
</html>

<?php $conn->close(); ?>