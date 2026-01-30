<?php
// 文件路径: app/includes/db.php

// 1. Docker服务名为 "db" (不能用 localhost)
// 2. 密码是 "rootpassword" (根据您的 docker-compose.yml)
$conn = new mysqli("db", "root", "rootpassword", "hospital");

// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置编码，防止乱码
mysqli_set_charset($conn, "utf8mb4");
?>