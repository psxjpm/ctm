<?php
// loads database connection and starts session
require("config.php");
// loads audit logging fucntion
require("audit_log.php");

// ensures user is logged in before accessing page 
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

// current logged in user 
$currentUser = $_SESSION["username"];
// retrieves user profile details from the database
$sql = "SELECT username, password, role FROM logins WHERE username = '$currentUser'";
$result = mysqli_query($conn, $sql);
$profile = mysqli_fetch_assoc($result);

// handles profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newUsername = trim($_POST["new_username"]); // new username input 
    $newPassword = trim($_POST["new_password"]); // new password input

    // checks fields arent empty
    if (empty($newUsername) || empty($newPassword)) {
        $message = "<p class='error-msg'>Username and password cannot be empty</p>";
    } else {
        // checks username isnt already being used 
        $check = mysqli_query($conn, "SELECT * FROM logins WHERE username = '$newUsername' AND username != '$currentUser'");
        if (mysqli_num_rows($check) > 0) {
            $message = "<p class='error-msg'>That username is already taken</p>";
        } else {
            // updates username and password
            $update = "UPDATE logins 
                       SET username = '$newUsername', password = '$newPassword'
                       WHERE username = '$currentUser'";
            mysqli_query($conn, $update);

            // updates sesiion username after change
            $_SESSION["username"] = $newUsername;
            // log the update 
            audit_log($conn, $_SESSION["username"], "UPDATE_PROFILE", "Changed username to $newUsername");
            $message = "<p class='success-msg'>Profile updated successfully</p>";

            // refreshes profile details 
            $currentUser = $newUsername;
            $sql = "SELECT username, password, role FROM logins WHERE username = '$currentUser'";
            $result = mysqli_query($conn, $sql);
            $profile = mysqli_fetch_assoc($result);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
     <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>My Profile</h2>
<!--displays messages-->
<?php if (isset($message)) echo $message; ?>

<!--shows profile details-->
<table>
    <tr>
        <th>Field</th>
        <th>Information</th>
    </tr>
    <tr>
        <td><strong>Username</strong></td>
        <td><?php echo $profile['username']; ?></td>
    </tr>
    <tr>
        <td><strong>Role</strong></td>
        <td><?php echo ucfirst($profile['role']); ?></td>
    </tr>
</table>

<!--profile update form-->
<form method="POST" action="view_profile.php" style="width:45%; margin:auto;">
    <label>New Username:</label>
    <input type="text" name="new_username" value="<?php echo $profile['username']; ?>" required>

    <label>New Password:</label>
    <div style="display:flex; align-items:center;">
        <input type="password" id="passwordField" name="new_password" value="<?php echo $profile['password']; ?>" required style="flex:1;">
        <!--for password visibility-->
        <!--tracks when the button is being clicked and displays password then hides it again when the button is no longer being clicked-->
        <button type="button" 
            onmousedown="showPassword()" 
            onmouseup="hidePassword()" 
            onmouseleave="hidePassword()" 
            style="margin-left:8px; padding:8px;">
        </button>
    </div>

    <button type="submit" class="blue-button">Save Changes</button>
</form>

<br>
<a href="<?php echo ($profile['role'] == 'administrator') ? 'admin_dashboard.php' : 'doctor_dashboard.php'; ?>" class="green-button">Back to Dashboard</a>

<script>
// defines funtion to show password when mouse is being clicked 
function showPassword() {
    document.getElementById("passwordField").type = "text";
}
// defines funtion to hide password again when mouse is no longer clicking the button
function hidePassword() {
    document.getElementById("passwordField").type = "password";
}
</script>
</body>
</html>