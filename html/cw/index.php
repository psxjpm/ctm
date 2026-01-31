<?php
// connects to database 
// starts session
// makes $conn and $_SESSION available
require("config.php");

// checks if an error message exists in the session then stores the message in $loginMessage to display on screen
// then deletes the message after to stop it showing after page is reloaded
if (isset($_SESSION["login_error"])) {
    $loginMessage = "<p class='error-msg'>" . $_SESSION["login_error"] . "</p>";
    unset($_SESSION["login_error"]);
} else {
    $loginMessage = "";
}
?>
<!DOCTYPE html>
<html>
<head> 
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!--displays login error messages or nothing if there is no error-->
    <?php echo $loginMessage; ?>
    <!--"POST" - sends data-->
    <!--method="authenticate.php" - form data will be processed in authenticate.php-->
    <form name="login" method="POST" action="authenticate.php">
            <!--required attribute prevents empty submission-->
            <label>Username *:</label><br>
            <input type="text" name="username" required>

            <!--works same as username input but characters are hidden-->
            <label>Password *:</label><br>
            <input type="password" name="password" required>

            <!--form is submitted and username and password are sent to authenticate.php-->
            <button type="submit" class="blue-button">Login</button>
    </form>
</body>
</html>
