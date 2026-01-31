<?php
require("config.php");

// code only runs when login form sends data using "POST"
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // collects username and password from the form
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // searches for matching username and password in the database 
    $sql = "SELECT * FROM logins WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    // if exactly one row is returned from the database the login is correct 
    if (mysqli_num_rows($result) == 1) {

        // stores user details into the session
        $row = mysqli_fetch_assoc($result);
        $_SESSION["username"] = $row['username'];
        $_SESSION["role"] = $row['role'];

        // redirects user based on role
        if ($row['role'] == "administrator") {
            header("Location: admin_dashboard.php");
        } else if ($row['role'] == "doctor") {
            header("Location: doctor_dashboard.php");
        }
        // stops page running after redirect 
        exit();

    // if no matching user is found: saves error message in the session, sends user back to login page, login page displays error once
    } else {
        $_SESSION["login_error"] = "Invalid username or password";
        header("Location: index.php");
        exit();
    }
}
?>