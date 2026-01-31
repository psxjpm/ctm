<?php
require("config.php");

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
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<script>
// updates the date and time on the screen
function updateBigClock() {
    const now = new Date(); // get the current date and time
    // display date
    document.getElementById("bigDate").textContent =
        now.toLocaleDateString("en-GB", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    // display time
    document.getElementById("bigClock").textContent =
        now.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit"});
}
// refreshes clock every second
setInterval(updateBigClock, 1000);
// runs clock immediately when page loads 
updateBigClock();
</script>

<!--main container and page content-->
<div class="page-container">
    <!--sidebar menu panel-->
    <div class="sidebar">
        <div class="sidebar-header">
            <!--label showing user role-->
            <div class="sidebar-title">Doctor</div>
            <!--welcome message for user-->
            <div class="sidebar-subtitle">Welcome, <?php echo $_SESSION["username"]; ?></div>
        </div>
        <!--sidebar for navigation to other pages-->
        <div class="sidebar-nav">
            <a href="search_patient.php">Search Patient</a>
            <a href="add_test.php">Add New Test</a>
            <a href="assign_test.php">Assign Test to Patient</a>
            <a href="parking_permit.php">Request Parking Permit</a>
        </div>
        <!--section for profile picture to view profile-->
        <div class="sidebar-profile">
            <a href="view_profile.php" class="profile-link">
                <img src="default_profile.png" alt="Profile" class="profile-picture">
            </a>
        </div>
        <!--footer for logout button at bottom of sidebar-->
        <div class="sidebar-footer">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <!--main area of the dashboard-->
    <div class="main-content">
        <div class="main-header">
            <h2>Doctor Control Panel</h2>
        </div>
        <!--center area for the date and time display-->
        <div class="clock-center">
            <div id="bigDate" class="big-date"></div>
            <div id="bigClock" class="big-clock"></div>
        </div>
    </div>
</div>
</body>
</html>