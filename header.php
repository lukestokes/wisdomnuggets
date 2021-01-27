<?php
include "objects.php";
include "wisdom_data.php";

// Begin the PHP session so we have a place to store the username
session_start();

if (isset($_GET["logout"])) {
    if ($_SESSION["completed"] > 0) {
        $user = new User($_SESSION['username']);
        $user->read();
        $user->saveSession($_SESSION["session_start"],$_SESSION["completed"],$_SESSION['auto_play']);        
    }

    // Unset all of the session variables.
    $_SESSION = array();
    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Finally, destroy the session.
    session_destroy();
    header("Location: index.php");
}

if (isset($_GET["save"]) && $_SESSION["completed"] > 0) {
    if (isset($_SESSION['username'])) {
        $user = new User($_SESSION['username']);
        $user->read();
        $user->saveSession($_SESSION["session_start"],$_SESSION["completed"],$_SESSION['auto_play']);
        $_SESSION["completed"] = 0;
    } else {
        header("Location: login_and_register.php");
    }
}

$login_status_string = "";

// If there is a username, they are logged in, and we'll show the logged-in view
if (isset($_SESSION['username'])) {
    $login_status_string = '<p>Logged in as ';
    $login_status_string .= $_SESSION['username'];
    $login_status_string .= ' <a href="?logout">Log Out</a> <a href="?viewStats">View stats</a></p>';
}

// If there is no username, they are logged out, so show them the login link
if (!isset($_SESSION['username'])) {
  $login_status_string = '<p>Not logged in';
  $login_status_string .= ' <a href="login_and_register.php">Log In</a></p>';
}
?>