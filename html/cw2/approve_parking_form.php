<?php
session_start();

$servername = "mariadb";
$username_db = "root";
$password_db = "rootpwd";
$dbname = "hospital";

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$conn = new mysqli("mariadb", "root", "rootpwd", "hospital");
if ($conn->connect_error) {
    die("Connection failed");
}

$sql = "SELECT * FROM parkingpermit WHERE status = 'pending'";
$result = $conn->query($sql);
?>
<html>
<head>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<h2>Parking Requests</h2>

<table>
    <tr>
        <th>Staff ID</th>
        <th>Reason</th>
        <th>Action</th>
    </tr>

<?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['staffno'] ?></td>
        <td><?= $row['reason'] ?></td>
        <td>
            <form method="post" action="approve_parking.php">
                <input type="hidden" name="parkingid" value="<?= $row['parkingid']?>">
                <button type="submit">Approve</button>
            </form>
            <form method="post" action="reject_parking.php">
                <input type="hidden" name="parkingid" value="<?= $row['parkingid']?>">
                <button type="submit">Reject</button>
                <input type="text" name="reason" placeholder="Reason for rejection" required>
            </form>
        </td>
    </tr>
<?php endwhile; ?>
</table>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div style="color: green; font-weight: bold; margin-bottom: 10px;">
        Action completed successfully!
    </div>
<?php endif; ?>

<div class="container" style="background-color:#f1f1f1">
    <a href="dashboard_admin.php" class="cancelbtn">Return</a>
  </div>
</body>
</html>