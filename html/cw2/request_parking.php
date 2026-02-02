<?php
session_start();

if (isset($_POST['final_submit'])) {


$servername = "mariadb";
$username_db = "root";
$password_db = "rootpwd";
$dbname = "hospital";

$conn = new mysqli("mariadb", "root", "rootpwd", "hospital");
if ($conn->connect_error) {
    die("Connection failed");
}

$parking_type = $_POST['parking_type'];
$car_details = $_POST['car_details'];
$price = $_POST['price'];
$staffno = $_SESSION['staffno'];

$status = 'pending';

$sql= "INSERT INTO parkingpermit (staffno, parking_type, car_details, price, status) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssds", $staffno, $parking_type, $car_details, $price, $status);

if ($stmt->execute()) {
     header("Location: request_parking_form.php?status=success");
    exit();
} else {
    echo "Error: " . $stmt->error; 
}
$conn->close();
}
?>