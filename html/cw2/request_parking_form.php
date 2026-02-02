<?php

session_start();  
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
// https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input/radio
// https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Structuring_content/HTML_table_basics

$selected_type = $_POST['parking_type'] ?? 'monthly';
$price = ($selected_type === 'yearly') ? 600.00 : 50.00;

?>

<html>
    <head>
 <link rel="stylesheet" href="css/styles.css">
</head>
<body>

 <div class="container" style="background-color:#0483aa">
    <a href="<?php
        if ($_SESSION['role'] === 'doctor') {
            echo 'dashboard_dr.php';
        } elseif ($_SESSION['role'] === 'admin') {
            echo 'dashboard_admin.php';
        }
    ?>" class="dashboardbtn">Dashboard</a>
</div>
<h2> Request Parking</h2>

<form action="request_parking.php" method="post">
  <div class="imgcontainer">
   
  </div>

  <div class="container">
    <fieldset>
    <legend>Select a parking type:</legend>

    <div>
        <input type="radio" id="monthly" name="parking_type" value="monthly" onchange="this.form.action='request_parking_form.php'; this.form.submit()" 
                       <?php if($selected_type === 'monthly') echo 'checked'; ?> />
        <label for="parking_type">Monthly</label>
    </div>

    <div>
        <input type="radio" id="yearly" name="parking_type" value="yearly" onchange="this.form.action='request_parking_form.php'; this.form.submit()" 
                       <?php if($selected_type === 'yearly') echo 'checked'; ?> />
        <label for="parking_type">Yearly</label>
    </div>
    </fieldset>
<div>
    <h3>Price: £<?php echo number_format($price, 2); ?></h3>
</div>

        <input type="hidden" name="price" value="<?php echo $price; ?>" />
        <label for="car_details"><b>Vehicle Registration</b></label>
        <input type="text" placeholder="Enter Vehicle Registration" name="car_details" required>
        <div>Staff No: <?php echo $_SESSION['staffno'] ?? 'Not set'; ?></div>

    <button type="submit" name="final_submit">Submit</button>
  </div>

  <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div style="color: green; font-weight: bold; margin-bottom: 10px;">
        Action completed successfully!
    </div>
<?php endif; ?>
</div>
</form>
</body>
</html>
