<?php
require("config.php");
require("audit_log.php");

// checks user is logged in before accessing page 
if(!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// ensures only administrators can access this page
if ($_SESSION["role"] !== "administrator") {
    header("Location: doctor_dashboard.php");
    exit();
}

// reads filter options
$where = [];
// only shows entries from a specific username 
if (!empty($_GET["user"])) {
    $u = mysqli_real_escape_string($conn, $_GET["user"]);
    $where[] = "username = '$u'";
}
// only shows specific actions 
if (!empty($_GET["action"])) {
    $a = mysqli_real_escape_string($conn, $_GET["action"]);
    $where[] = "action = '$a'";
}

$sql = "SELECT * FROM audit_log";
// adds any filters 
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
// newest activity first
$sql .= " ORDER BY timestamp DESC";
// runs the SQL and stores the results 
$logs = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h2>Audit Log</h2>
<!-- lets the admin filter the list -->
<form method="GET" action="audit_trail.php">
    <label>Filter by Username:</label>
    <input type="text" name="user">

    <label>Filter by Action:</label>
    <input type="text" name="action">

    <button type="submit" class="blue-button">Apply Filters</button>
</form>

<!-- section heading -->
<h3>Audit Log Records</h3>

<!-- uses global table styling -->
<table>
    <tr>
        <th>Username</th>
        <th>Action</th>
        <th>Details</th>
        <th>Timestamp</th>
    </tr>

    <!-- displayes each result returned from the database in a table row --> 
    <?php if (mysqli_num_rows($logs) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($logs)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row["username"]); ?></td>
                <td><?php echo htmlspecialchars($row["action"]); ?></td>
                <td><?php echo htmlspecialchars($row["details"]); ?></td>
                <td><?php echo htmlspecialchars($row["timestamp"]); ?></td>
            </tr>
        <?php } ?>
    <!-- if no records have been found -->
    <?php else: ?>
        <tr>
            <td colspan="4">No records found.</td>
        </tr>
    <?php endif; ?>
</table>

<br>
<a href="admin_dashboard.php" class="green-button" style="width:200px;">Back to Dashboard</a>

</body>
</html>