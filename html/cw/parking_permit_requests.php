<?php
// load configuration settings and start session
require("config.php");
// load audit logging functions
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

// handles approvals 
if (isset($_POST["approve"])) {
    $doctor = $_POST["username"]; // doctor username
    $permit_number = trim($_POST["permit_number"]); // permit number as assigned by admin
    // get permit type for request as selected by user 
    $getType = mysqli_query($conn, "SELECT permit_type FROM parking_permit_requests WHERE username = '$doctor' AND status = 'Pending' LIMIT 1"); 
    $row = mysqli_fetch_assoc($getType);
    $permit_type = $row['permit_type'];
    // approval date and time
    $approval_date = date("Y-m-d H:i:s");
    // calculate end date based on permit type amd approval date
    switch ($permit_type) {
        case "Monthly":
            $end_date = date("Y-m-d", strtotime("+1 month", strtotime($approval_date)));
            break;
        case "Yearly":
            $end_date = date("Y-m-d", strtotime("+1 year", strtotime($approval_date)));
            break;
        default:
            $end_date = NULL;
    }
    // converts NULLs into format compatible with SQL
    if ($end_date === NULL) {
        $endDateSQL = "NULL";
    } else {
        $endDateSQL = "'$end_date'";
    }
    // update request as accepted
    $sql = "UPDATE parking_permit_requests
            SET status = 'accepted', permit_number = '$permit_number', approval_date = '$approval_date', end_date = $endDateSQL
            WHERE username = '$doctor' AND status = 'Pending'";
    mysqli_query($conn, $sql);
    // add action to audit log
    audit_log($conn, $_SESSION["username"], "PERMIT_APPROVE", "Approved permit for $doctor, Permit No: $permit_number (Type: $permit_type)"); 
}

// handles rejections
if (isset($_POST["reject"])) {
    $doctor = $_POST["username"]; // doctor username
    $reason = isset($_POST['rejection_reason']) ? trim($_POST["rejection_reason"]): " "; // reason for rejection
    // updates request as rejected 
    $sql = "UPDATE parking_permit_requests
            SET status = 'rejected', rejection_reason = '$reason'
            WHERE username = '$doctor' AND status = 'Pending'";
    mysqli_query($conn, $sql);
    // add action to audit log 
    audit_log($conn, $_SESSION["username"], "PERMIT_REJECT", "Rejected permit for $doctor - Reason: $reason");
}

// load all permit requests 
$requests = mysqli_query($conn, "SELECT * FROM parking_permit_requests ORDER BY request_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Parking Permit Requests</h2>

<!--table displaying requests and status-->
<table>
<tr>
    <th>Doctor</th>
    <th>Car</th>
    <th>Registration</th>
    <th>Permit Type</th>
    <th>Cost</th>
    <th>Status</th>
    <th>Approval Date</th>
    <th>End Date</th>
    <th>Action</th>
</tr>

<!--loops through each permit request-->
<?php while ($r = mysqli_fetch_assoc($requests)) { ?>
<tr>
    <td><?php echo $r['username']; ?></td>
    <td><?php echo $r['car_make'] . " " . $r['car_model']; ?></td>
    <td><?php echo $r['car_reg']; ?></td>
    <td><?php echo ucfirst($r['permit_type']); ?></td>
    <td>£<?php echo $r['cost']; ?></td>
    <td><?php echo ucfirst($r['status']); ?></td>
    <td><?php echo $r['approval_date'] ? $r['approval_date'] : "—"; ?></td>
    <td><?php echo $r['end_date'] ? $r['end_date'] : "—"; ?></td>
    <td>
        <!--display approval and rejection options when status is pending-->
        <?php if ($r['status'] == 'Pending') { ?>
            <!--approval option-->
            <form method="POST" action="parking_permit_requests.php" style="margin-bottom:10px;">
                <input type="hidden" name="username" value="<?php echo $r['username']; ?>">
                <input type="text" name="permit_number" placeholder="Permit Number" required>
                <button type="submit" name="approve" class="green-button">Approve</button>
            </form>
            <!--rejection otpion-->
            <form method="POST" action="parking_permit_requests.php">
                <input type="hidden" name="username" value="<?php echo $r['username']; ?>">
                <input type="text" name="rejection_reason" placeholder="Reason for rejection" required>
                <button type="submit" name="reject" class="red-button">Reject</button>
            </form>
        <!--displays assigned permit number if accepted-->
        <?php } elseif ($r['status'] == 'accepted') { ?>
            Permit #: <?php echo $r['permit_number']; ?>
        <!--displays rejection reason if rejected-->
        <?php } elseif ($r['status'] == 'rejected') { ?>
            Rejected — <?php echo $r['rejection_reason']; ?>
        <?php } ?>
    </td>
</tr>
<?php } ?> <!--ends loop-->
</table>

<br>
<a href="admin_dashboard.php" class="green-button" style="width:200px;">Back to Dashboard</a>

</body>
</html>