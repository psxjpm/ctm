<?php
// 管理员账户检查和调试页面
// 使用后请删除此文件!

require_once '../includes/db.php';

echo "<h1>Admin Account Debug Tool</h1>";
echo "<p style='color: red;'><strong>WARNING: Delete this file after use!</strong></p>";
echo "<hr>";

// 检查1: 数据库连接
echo "<h2>1. Database Connection</h2>";
if ($conn) {
    echo "✅ <span style='color: green;'>Database connected successfully</span><br>";
} else {
    echo "❌ <span style='color: red;'>Database connection failed: " . $conn->connect_error . "</span><br>";
    exit();
}

// 检查2: admin表是否存在
echo "<h2>2. Check if 'admin' table exists</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'admin'");
if ($table_check->num_rows > 0) {
    echo "✅ <span style='color: green;'>Table 'admin' exists</span><br>";
} else {
    echo "❌ <span style='color: red;'>Table 'admin' does NOT exist!</span><br>";
    echo "<p><strong>Solution:</strong> Run this SQL in phpMyAdmin:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px;'>";
    echo "CREATE TABLE admin (\n";
    echo "    username VARCHAR(50) PRIMARY KEY,\n";
    echo "    password VARCHAR(50) NOT NULL\n";
    echo ");\n\n";
    echo "INSERT INTO admin (username, password) VALUES ('jelina', 'iron99');\n";
    echo "</pre>";
    exit();
}

// 检查3: jelina账户是否存在
echo "<h2>3. Check if 'jelina' account exists</h2>";
$user_check = $conn->query("SELECT * FROM admin WHERE username = 'jelina'");
if ($user_check->num_rows > 0) {
    echo "✅ <span style='color: green;'>User 'jelina' exists</span><br>";
    $user_data = $user_check->fetch_assoc();
    echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th style='padding: 5px;'>Username</th><th style='padding: 5px;'>Password</th></tr>";
    echo "<tr><td style='padding: 5px;'>" . htmlspecialchars($user_data['username']) . "</td>";
    echo "<td style='padding: 5px;'>" . htmlspecialchars($user_data['password']) . "</td></tr>";
    echo "</table>";
} else {
    echo "❌ <span style='color: red;'>User 'jelina' does NOT exist!</span><br>";
    echo "<p><strong>Solution:</strong> Run this SQL in phpMyAdmin:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px;'>";
    echo "INSERT INTO admin (username, password) VALUES ('jelina', 'iron99');\n";
    echo "</pre>";
    exit();
}

// 检查4: 密码验证
echo "<h2>4. Password Verification Test</h2>";
$test_password = 'iron99';
$stored_password = $user_data['password'];

echo "Stored password in DB: <strong>" . htmlspecialchars($stored_password) . "</strong><br>";
echo "Test password: <strong>" . htmlspecialchars($test_password) . "</strong><br>";

if ($test_password === $stored_password) {
    echo "✅ <span style='color: green;'>Password matches!</span><br>";
} else {
    echo "❌ <span style='color: red;'>Password does NOT match!</span><br>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>Password has extra spaces</li>";
    echo "<li>Password encoding is different</li>";
    echo "<li>Password was changed</li>";
    echo "</ul>";
    echo "<p><strong>Solution:</strong> Reset password:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px;'>";
    echo "UPDATE admin SET password = 'iron99' WHERE username = 'jelina';\n";
    echo "</pre>";
}

// 检查5: 显示所有管理员账户
echo "<h2>5. All Admin Accounts</h2>";
$all_admins = $conn->query("SELECT * FROM admin");
if ($all_admins->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th style='padding: 5px;'>Username</th><th style='padding: 5px;'>Password</th></tr>";
    while ($row = $all_admins->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td style='padding: 5px;'>" . htmlspecialchars($row['password']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No admin accounts found.</p>";
}

// 检查6: 测试登录逻辑
echo "<h2>6. Login Logic Test</h2>";
$test_username = 'jelina';
$test_password = 'iron99';

$stmt = $conn->prepare("SELECT username, password FROM admin WHERE username = ?");
$stmt->bind_param("s", $test_username);
$stmt->execute();
$result = $stmt->get_result();

echo "Query executed...<br>";
echo "Rows returned: <strong>" . $result->num_rows . "</strong><br>";

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    echo "Username from DB: <strong>" . htmlspecialchars($row['username']) . "</strong><br>";
    echo "Password from DB: <strong>" . htmlspecialchars($row['password']) . "</strong><br>";
    
    if ($test_password === $row['password']) {
        echo "✅ <span style='color: green; font-size: 18px;'><strong>LOGIN SHOULD WORK!</strong></span><br>";
    } else {
        echo "❌ <span style='color: red;'>Password comparison failed</span><br>";
    }
} else {
    echo "❌ <span style='color: red;'>User not found in database</span><br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all checks above show ✅, the login should work.</p>";
echo "<p>If you see any ❌, follow the solutions provided.</p>";
echo "<p style='color: red;'><strong>IMPORTANT: Delete this file (check_admin.php) after fixing the issue!</strong></p>";

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 900px;
    margin: 0 auto;
}
h1 {
    color: #333;
}
h2 {
    color: #666;
    border-bottom: 2px solid #ddd;
    padding-bottom: 5px;
    margin-top: 30px;
}
</style>