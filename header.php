<?php
include "objects.php";
include "wisdom_data.php";

// Begin the PHP session so we have a place to store the username
session_start();

if (isset($_GET["logout"])) {
    if ($_SESSION["completed"] > 0) {
        $user = new User($_SESSION['username'],$_SESSION['fio_address']);
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
        $user = new User($_SESSION['username'],$_SESSION['fio_address']);
        $user->read();
        $user->saveSession($_SESSION["session_start"],$_SESSION["completed"],$_SESSION['auto_play']);
        $_SESSION["completed"] = 0;
    } else {
        header("Location: index.php?login");
    }
}

$login_status_string = "";

if (isset($_GET["identity_proof"]) && $_GET["identity_proof"] != "") {
  $proof = json_decode($_GET["identity_proof"], true);
  $guzzle = new GuzzleHttp\Client();
  try {
    $identity_response = $guzzle->post('https://eosio.greymass.com/prove', [
        GuzzleHttp\RequestOptions::JSON => ['proof' => $proof] // or 'json' => [...]
    ]);
    $identity_results = json_decode($identity_response->getBody(), true);
    /*
    // PUB_K1 format
    foreach ($identity_results["permissions"] as $i => $permission) {
        //var_dump($permission);
        if ($permission->perm_name == "active") {
            if (isset($permission->required_auth->keys[0])) {
                $fio_public_key = $permission->required_auth->keys[0]->key;
            }
        }
    }
    */
    $user = new User($identity_results["account_name"]);
    $user->last_login = time();
    $user->last_login_ip = $_SERVER['REMOTE_ADDR'];
    $fio_addresses = $user->getFIOAddresses($client);
    $_SESSION['username'] = $identity_results["account_name"];
    $_SESSION['fio_address'] = "";
    if (count($fio_addresses)) {
      $_SESSION['fio_address'] = $fio_addresses[0];
      $user->fio_address = $fio_addresses[0];
    }
    $_SESSION["session_start"] = time();
    $user->save();
    if (count($fio_addresses) > 1) {
        $login_status_string .= '
            <form method="GET" id="fio_address_selection_form">
            <input type="hidden" id="next_action" name="next_action" value="use_fio_address">
        ';
        foreach ($fio_addresses as $key => $fio_address) {
            $login_status_string .= '
            <div class="form-check">
            <input class="form-check-input" name="user_fio_address" id="user_fio_address_' . $key . '" type="radio" value="' . $fio_address . '">
            <label class="form-check-label" for="user_fio_address_' . $key . '">' . $fio_address . '</label>
            </div>';
        }
        $login_status_string .= '
            <button type="submit" class="btn btn-primary mb-3">Use This Address</button>
            </form>
        ';
    }
  } catch (Exception $e) {
    $login_status_string .= '<div class="alert alert-danger" role="alert">' . $e->getMessage() . '<br />Pleae login again.</div>';
  }
}

// If there is a username, they are logged in, and we'll show the logged-in view
if (isset($_SESSION['username'])) {
    if (isset($_GET["next_action"]) && $_GET["next_action"] == "use_fio_address") {
        $user = new User($_SESSION['username'],$_SESSION['fio_address']);
        $user->read();
        if ($user->isOwnedFIOAddress($client,$_GET["user_fio_address"])) {
            $_SESSION['fio_address'] = $_GET["user_fio_address"];
            $user = new User($_SESSION['username'],$_SESSION['fio_address']);
            $user->read();
            if (!$user->userExists()) {
                $user->save();
            }
        }
    }

    $login_status_string .= 'Logged in as ';
    $login_status_string .= $_SESSION['username'];
    if ($_SESSION['fio_address'] != "") {
        $login_status_string .= ": " . $_SESSION['fio_address'];
    }
    $login_status_string .= ' <a href="?logout">Log Out</a>';

}

// If there is no username, they are logged out, so show them the login link
if (!isset($_SESSION['username'])) {
  $login_status_string .= 'Not logged in';
  $login_status_string .= ' <a href="?login">Log In</a>';
}
?>