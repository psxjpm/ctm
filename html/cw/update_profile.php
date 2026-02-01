<?php
require_once 'config/db.inc.php';
require_once 'includes/auth_check.php';

$pdo = getDBConnection();
$message = '';

// 获取当前医生的ID
$doctor_id = $_SESSION['doctor_id'] ?? 0;

// 获取当前医生信息
if ($doctor_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM DOCTOR WHERE Doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        $message = '<div class="message error">Doctor profile not found!</div>';
        $doctor = [];
    }
} else {
    $message = '<div class="message error">Invalid doctor session!</div>';
    $doctor = [];
}

// 初始化医生信息变量
$doctorFirstName = $doctor['FirstName'] ?? '';
$doctorLastName = $doctor['LastName'] ?? '';
$doctorStaffNo = $doctor['Staff_no'] ?? '';
$doctorSpecialisation = $doctor['Specialisation'] ?? '';
$doctorAddress = $doctor['Address_street'] ?? '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 更新个人信息
    if (isset($_POST['update_profile'])) {
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $address = $_POST['address'] ?? '';
        
        if (empty($firstName) || empty($lastName)) {
            $message = '<div class="message error">First name and last name are required!</div>';
        } else {
            try {
                // 更新医生信息
                $stmt = $pdo->prepare("
                    UPDATE DOCTOR 
                    SET FirstName = ?, 
                        LastName = ?, 
                        Address_street = ?
                    WHERE Doctor_id = ?
                ");
                $stmt->execute([$firstName, $lastName, $address, $doctor_id]);
                
                // 更新会话中的医生姓名
                $_SESSION['doctor_name'] = $firstName . ' ' . $lastName;
                
                // 更新本地变量
                $doctorFirstName = $firstName;
                $doctorLastName = $lastName;
                $doctorAddress = $address;
                
                $message = '<div class="message success">Profile updated successfully!</div>';
                
                // 重新获取医生信息
                $stmt = $pdo->prepare("SELECT * FROM DOCTOR WHERE Doctor_id = ?");
                $stmt->execute([$doctor_id]);
                $doctor = $stmt->fetch();
                
            } catch (Exception $e) {
                $message = '<div class="message error">Error updating profile: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        
    } 
    // 更改密码
    elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // 验证当前密码
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ? AND user_type = 'doctor'");
        $stmt->execute([$_SESSION['username']]);
        $user = $stmt->fetch();
        
        if (!$user || $user['password'] !== $currentPassword) {
            $message = '<div class="message error">Current password is incorrect</div>';
        } elseif ($newPassword !== $confirmPassword) {
            $message = '<div class="message error">New passwords do not match</div>';
        } elseif (strlen($newPassword) < 6) {
            $message = '<div class="message error">Password must be at least 6 characters</div>';
        } else {
            try {
                // 更新密码
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$newPassword, $_SESSION['username']]);
                $message = '<div class="message success">Password changed successfully!</div>';
            } catch (Exception $e) {
                $message = '<div class="message error">Error changing password: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - QMC</title>
    <link rel="stylesheet" href="/cw/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Update Profile</h1>
        
        <?php echo $message; ?>
        
        <!-- 个人资料编辑区域 -->
        <div class="dashboard-grid">
            <!-- 个人信息部分 -->
            <div class="card">
                <h2>Personal Information</h2>
                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" 
                                   value="<?php echo htmlspecialchars($doctorFirstName); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" 
                                   value="<?php echo htmlspecialchars($doctorLastName); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Staff Number (NHS)</label>
                        <input type="text" value="<?php echo htmlspecialchars($doctorStaffNo); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" value="<?php echo htmlspecialchars($doctorSpecialisation); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3" placeholder="Enter your address"><?php echo htmlspecialchars($doctorAddress); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
            
            <!-- 密码更改部分 -->
            <div class="card">
                <h2>Change Password</h2>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>