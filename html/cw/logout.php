<?php
// before you can modify or delete a session, PHP must first access it
// loads current session into memory
session_start();
// removes all session variables e.g. username, role, messages
// session still exists, its just empty
session_unset();
// fully deletes session from server 
// there is no active login session, user is considered logged out
session_destroy();
// redirects to login page, prevents user staying on protected pages after being logged out
header("Location: index.php");
// stops script immediately 
exit();
?>