<?php
session_start();  
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

?>

<html>
    <head>
 <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<h2>Update your profile</h2>
<form action="update_profile.php" method="post">
  <div class="imgcontainer">
   
  </div>

  <div class="container">
    <label for="username"><b> New Username / Firstname</b></label>
    <input type="text" placeholder="Enter new username or firstname" name="username">

    <label for="password"><b>New Password</b></label>
    <input type="password" placeholder="Enter new password" name="password">
        
    <button type="submit">Comfirm changes</button>

  </div>
<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div style="color: green; font-weight: bold; margin-bottom: 10px;">
        Action completed successfully!
    </div>
<?php endif; ?>

  <div class="container" style="background-color:#f1f1f1"> 
    <a href="<?php
        if ($_SESSION['role'] === 'doctor') {
            echo 'dashboard_dr.php';
        } elseif ($_SESSION['role'] === 'admin') {
            echo 'dashboard_admin.php';
        }
    ?>" class="cancelbtn">Return</a>
  </div>

</form>



</body>
</html>


