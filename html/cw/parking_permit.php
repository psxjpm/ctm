<?php
// loads database connection and starts session
require("config.php");
require("audit_log.php");

// checks user is logged in before accessing page 
if(!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// restricted to only doctors 
if ($_SESSION["role"] !== "doctor") {
    header("Location: admin_dashboard.php");
    exit();
}

// logged in doctor username 
$doctor = $_SESSION["username"];
// stores messages to be shown to user 
$message = "";

// retrieves most recent request made by the doctor that is currently logged in 
$request = mysqli_query($conn, "SELECT * FROM parking_permit_requests WHERE username = '$doctor' ORDER BY request_date DESC LIMIT 1");
$requestData = mysqli_fetch_assoc($request);

// handles new permit request submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($requestData == null || $requestData['status'] == 'rejected')) {

    $car_make = trim($_POST["car_make"]);
    $car_model = trim($_POST["car_model"]);
    $car_reg = trim($_POST["car_reg"]);
    $permit_type = $_POST["permit_type"];
    // set depending on selected permit type
    $cost = ($permit_type == "Monthly") ? 30 : 300;
    // inserts new request into database
    $sql = "INSERT INTO parking_permit_requests 
            (username, car_make, car_model, car_reg, permit_type, cost)
            VALUES ('$doctor', '$car_make', '$car_model', '$car_reg', '$permit_type', '$cost')";
    // updates message if insert is successful
    if (mysqli_query($conn, $sql)) {
        audit_log($conn, $_SESSION["username"], "PERMIT_REQUEST", "Requested $permit_type for car $car_reg");
        $message = "<p class='success-msg'>Request submitted successfully</p>";
        $request = mysqli_query($conn, "SELECT * FROM parking_permit_requests WHERE username = '$doctor' ORDER BY request_date DESC LIMIT 1");
        $requestData = mysqli_fetch_assoc($request);
    // shows error message if request is unsuccessful
    } else {
        $message = "<p class='error-msg'>Error submitting request</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <script>
        // updates cost displayed when permi type is selected 
        function updateCost() {
            let type = document.getElementById("permit_type").value;
            document.getElementById("costDisplay").innerText = type === "Monthly" ? "£30" : "£300";
        }
    </script>
</head>
<body>

<h2>Parking Permit</h2>
<?php echo $message; ?> <!--displays success/ error message-->

<!--displays request form if the doctor has no request currently or the previous one was rejected-->
<?php if ($requestData == null || $requestData['status'] == 'rejected') { ?>

<form method="POST" action="parking_permit.php">
    <?php 
        // shows error message if request was rejected
        if ($requestData && $requestData['status'] == 'rejected') {
            echo "<p class='error-msg'>Last request rejected — Reason: {$requestData['reject_reason']}</p>";
        }
    ?>

        <label>Car Make:</label>
        <input type="text" name="car_make" required>

        <label>Car Model:</label>
        <input type="text" name="car_model" required>

        <label>Registration Number:</label>
        <input type="text" name="car_reg" required>

        <label>Permit Type:</label>
        <select name="permit_type" id="permit_type" onchange="updateCost()">
            <option value="">-- Choose Permit Type --</option>
            <option value="Monthly">Monthly (£30)</option>
            <option value="Yearly">Yearly (£300)</option>
        </select>

    <p style="text-align:center; font-weight:bold;">
        Cost: <span id="costDisplay"></span>
    </p>

        <button type="submit" class="blue-button">Submit Request</button>
</form>

<?php } else { ?> <!--doctor already has a live request (either pending or accepted)-->

<table>
    <tr>
        <th>Car</th>
        <th>Reg</th>
        <th>Type</th>
        <th>Cost</th>
        <th>Status</th>
        <th>Approval Date</th>
        <th>End Date</th>
        <th>Details</th>
    </tr>
    <tr>
        <td><?php echo $requestData['car_make'] . " " . $requestData['car_model']; ?></td>
        <td><?php echo $requestData['car_reg']; ?></td>
        <td><?php echo ucfirst($requestData['permit_type']); ?></td>
        <td><?php echo $requestData['cost']; ?></td>
        <td><?php echo ucfirst($requestData['status']); ?></td>
        <td><?php echo $requestData['approval_date'] ? $requestData['approval_date'] : "—"; ?></td>
        <td><?php echo $requestData['end_date'] ? $requestData['end_date'] : "—"; ?></td>
        <td>
            <?php
                if ($requestData['status'] == 'Pending') echo "Awaiting admin review";
                elseif ($requestData['status'] == 'Accepted') echo "Permit #: " . $requestData['permit_number'];
                elseif ($requestData['status'] == 'Rejected') echo "Reason: " . $requestData['rejection_reason'];
            ?>
        </td>
    </tr>
</table>

<?php } ?>

<br>
<a href="doctor_dashboard.php" class="green-button" style="width:200px;">Back to Dashboard</a>

</body>
</html>