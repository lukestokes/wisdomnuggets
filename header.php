<?php
include "objects.php";
include "wisdom_data.php";

$Faucet = new Faucet($client);

$title = "Wisdom Nuggets! Have Fun and Win Crypto Programming Your Brain";
$description = "Reality is the result of our actions initiated by our thoughts. Have fun playing a game to help you memorize key principles, maxims, logical fallacies, and more for clear thinking and success. To improve the world, start with yourself.";
$image = "https://wisdomnuggets.lukestokes.info/images/owl-4783407_640.png";
$url = "https://wisdomnuggets.lukestokes.info/";
$onboarding_pitch = '<a href="https://fioprotocol.io/free-fio-addresses/" target="_blank">get yourself a FIO Address</a> and import your private key into <a href="https://greymass.com/anchor/" target="_blank">Anchor Wallet by Greymass</a> to login. <a href="https://peakd.com/game/@lukestokes/wisdom-nuggets-a-fio-token-faucet-game-over-usd1-000-in-the-pot" target="_blank">Learn more here</a>.';

// Begin the PHP session so we have a place to store the username
session_start();

if (isset($_GET["logout"])) {
    if ($_SESSION["completed"] > 0) {
        $user = new User($_SESSION['username']);
        $user->read();
        $user->saveSession($_SESSION["session_start"],$_SESSION["completed"],$_SESSION['auto_play'],$_SESSION["types"]);
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
        $user->saveSession($_SESSION["session_start"],$_SESSION["completed"],$_SESSION['auto_play'],$_SESSION["types"]);
        $_SESSION["completed"] = 0;
    } else {
        header("Location: index.php?login");
    }
}

/* Set things according to the form */

$type = "";
$category = "";
$type_category = "";
if (isset($_GET["type_category"]) && $_GET["type_category"] != "") {
    $type_category = strip_tags($_GET["type_category"]);
    $values = explode("|", $type_category);
    $type = $values[0];
    $category = $values[1];
}
$auto_play = 0;
if (isset($_GET["auto_play"]) && $_GET["auto_play"] != "") {
    $auto_play = strip_tags($_GET["auto_play"]);
    $_SESSION["auto_play"] = $auto_play;
} else {
  if (isset($_SESSION["auto_play"])) {
    $auto_play = $_SESSION["auto_play"];
  }
}
if (isset($_GET["customized"]) && $_GET["customized"] == "true") {
    $_SESSION["types"] = $Wisdom->getTypesFromGet();
}

if (isset($_SESSION["types"]) && count($_SESSION["types"]) > 0) {
    $Wisdom->setActiveChunks($_SESSION["types"]);
}

if (!isset($_SESSION["completed"])) {
  $_SESSION["completed"] = 0;
}

$login_status_string = "";

if (isset($_GET["show_pending"])) {
    $login_status_string .= '<div class="alert alert-info" role="alert">Pending Payments: <br />';
    $FaucetPayments = $Faucet->getPayments(["status","=","Pending"]);
    ob_start();
    foreach ($FaucetPayments as $key => $FaucetPayment) {
        print $FaucetPayment->_id . ": ";
        $FaucetPayment->print();
    }
    $pending = ob_get_contents();
    ob_end_clean();
    $login_status_string .= $pending . '</div>';
}

if (isset($_SESSION["previous_answers"]) && isset($_GET["previous_answers"])) {
  if ($_SESSION["previous_answers"] == $_GET["previous_answers"] && $_GET["previous_answers"] != "") {
    $_SESSION["completed"]++;
    $Faucet = new Faucet($client);
    if (isset($_SESSION['username']) && $_SESSION['fio_address'] != "") {
        $user = new User($_SESSION['username']);
        $user_exists = $user->read();
        if ($user_exists) {
            $result = $Faucet->isWinner($_SESSION["completed"], $user);
            if ($result["winner"]) {
                $payments = $Faucet->getPayments([["actor","=",$user->actor],["status","=","Pending"]]);
                $total_pending = 0;
                foreach ($payments as $payment) {
                    $total_pending += $payment->amount;
                }
                $max_pending_to_allow = 40;
                if ($total_pending > $max_pending_to_allow) {
                    $login_status_string .= '<div class="alert alert-success" role="alert">You have over ' . $max_pending_to_allow . ' FIO in pending rewards. Let\'s save some rewards for others to enjoy today, okay? Wisdom is its own reward anyway, right? Needed >' . $result["threshold"] . ', rolled a ' . $result["pick"] . '.</div>';
                } else {
                    $FaucetPayment = $Faucet->distribute($user);
                    $login_status_string .= '<div class="alert alert-success" role="alert"><b>Congratulations!</b><br />You won ' . $FaucetPayment->amount . ' FIO (pending approval). Needed >' . $result["threshold"] . ', rolled a ' . $result["pick"] . '.</div>';
                    $user->saveSession($_SESSION["session_start"],$_SESSION["completed"],$_SESSION['auto_play'],$_SESSION["types"]);
                    $_SESSION["completed"] = 0;
                }
            } else {
                $login_status_string .= '<div class="alert alert-info" role="alert">No FIO reward this time. Your random roll was ' . $result["pick"] . ', but you needed a number higher than ' . $result["threshold"] . '.</div>';
            }
        }
    } else {
        $result = $Faucet->isWinner($_SESSION["completed"]);
        if ($result["winner"]) {
            $login_status_string .= '<div class="alert alert-info" role="alert"><b>You Would Have Won!</b><br />You would have won ' . $Faucet->getRewardAmount() . ' <a href="https://www.coingecko.com/en/coins/fio-protocol" target="_blank">FIO Tokens</a> if you were logged in with your own FIO Address. To start winning, ' . $onboarding_pitch . ' Needed >' . $result["threshold"] . ', rolled a ' . $result["pick"] . '.</div>';
        } else {
            $login_status_string .= '<div class="alert alert-info" role="alert">No FIO reward this time. Your random roll was ' . $result["pick"] . ', but you needed a number higher than ' . $result["threshold"] . '.</div>';
        }
    }
  }
}

if (isset($_GET["identity_proof"]) && $_GET["identity_proof"] != "") {
  $proof = json_decode($_GET["identity_proof"], true);
  try {
    $identity_response = $client->post('https://eosio.greymass.com/prove', [
        GuzzleHttp\RequestOptions::JSON => ['proof' => $proof] // or 'json' => [...]
    ]);
    $identity_results = json_decode($identity_response->getBody(), true);
    $user = new User($identity_results["account_name"]);
    $user_exists = $user->read();
    if ($user_exists) {
        if (count($user->types)) {
            $_SESSION['types'] = $user->types;
            $Wisdom->setActiveChunks($_SESSION["types"]);
        }
        $auto_play = $_SESSION["auto_play"] = $user->auto_play;
    }
    $user->last_login = time();
    $user->last_login_ip = $_SERVER['REMOTE_ADDR'];
    $_SESSION['username'] = $identity_results["account_name"];
    $_SESSION['fio_address'] = $user->fio_address;
    $_SESSION["session_start"] = time();
    if ($user->fio_address == "") {
        $fio_addresses = $user->getFIOAddresses($client);
        if (count($fio_addresses)) {
          $_SESSION['fio_address'] = $fio_addresses[0];
          $user->fio_address = $fio_addresses[0];
          if (count($fio_addresses) > 1) {
            $login_status_string .= $user->getFIOAddressSelectionForm();
          }
        }
    }
    $user->save();
  } catch (Exception $e) {
    $login_status_string .= '<div class="alert alert-danger" role="alert">' . $e->getMessage() . '<br />Pleae login again.</div>';
  }
}

// If there is a username, they are logged in, and we'll show the logged-in view
if (isset($_SESSION['username'])) {
    if (isset($_GET["next_action"]) && $_GET["next_action"] == "change_fio_address") {
        $user = new User($_SESSION['username']);
        $user->read();
        $user->fio_addresses = array(); // force a fresh chain query
        $fio_addresses = $user->getFIOAddresses($client);
        if (count($fio_addresses) > 1) {
            $login_status_string .= $user->getFIOAddressSelectionForm();
        }
    }

    if (isset($_GET["next_action"]) && $_GET["next_action"] == "use_fio_address") {
        $user = new User($_SESSION['username']);
        $user->read();
        if ($user->isOwnedFIOAddress($client,$_GET["user_fio_address"])) {
            $_SESSION['fio_address'] = $_GET["user_fio_address"];
            $user->fio_address = $_SESSION['fio_address'];
            $user->save();
        }
    }

    $login_status_string .= 'Logged in: ';
    if ($_SESSION['fio_address'] != "") {
        $login_status_string .= $_SESSION['fio_address'] . ' [<a href="?next_action=change_fio_address">change</a>]';
    } else {
        $login_status_string .= $_SESSION['username'] . ' (<a href="https://fioprotocol.io/free-fio-addresses/" target="_blank">no FIO Address</a>)';
    }
    $login_status_string .= ' [<a href="?logout">log out</a>]';

}

// If there is no username, they are logged out, so show them the login link
if (!isset($_SESSION['username'])) {
  $login_status_string .= '[<a href="?login">log in</a>]';
}

$Nugget = null;
if (isset($_GET["type"]) && $_GET["type"] != "" && isset($_GET["nugget_id"]) && $_GET["nugget_id"] != "") {
    $Nugget = $Wisdom->getEntry($_GET["type"], $_GET["nugget_id"]);
}
if (is_null($Nugget)) {
    $Nugget = $Wisdom->getRandom($type, $category);
}
$display = (($Nugget->title == "") ? $Nugget->category : $Nugget->category . ": " . $Nugget->title);
$grouped_words = $Nugget->createWordGroup();

$color_schemes = array(
    0 => array(
        '233d4d',
        'fe7f2d',
        'fcca46',
        'a1c181',
        '619b8a',
    ),
    1 => array(
        'ef476f',
        'ffd166',
        '06d6a0',
        '118ab2',
        '073b4c',
    ),
    2 => array(
        '5f0f40',
        '9a031e',
        'fb8b24',
        'e36414',
        '0f4c5c',
    ),
    3 => array(
        'ffbe0b',
        'fb5607',
        'ff006e',
        '8338ec',
        '3a86ff',
    ),
    4 => array(
        'f72585',
        '7209b7',
        '3a0ca3',
        '4361ee',
        '4cc9f0',
    ),
);

$key = array_rand($color_schemes);
$color_scheme = $color_schemes[$key];