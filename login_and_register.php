<?php
include "header.php";

$fio_address = isset($_POST["fio_address"]) ? strip_tags($_POST["fio_address"]) : "";
$password = isset($_POST["password"]) ? strip_tags($_POST["password"]) : "";
$password_again = isset($_POST["password_again"]) ? strip_tags($_POST["password_again"]) : "";

$errorString = "";

if (isset($_POST["next_action"]) && $_POST["next_action"] == "save_login") {
  if ($fio_address == "" || $password == "" || $password_again == "") {
    $errorString = "Required fields missing.";
  }
  if ($errorString == "") {
    $user = new User($fio_address);
    if ($user->userExists()) {
      $errorString = "This user already exists. Please login.";
    }
  }
  if ($errorString == "") {
    if ($password == $password_again) {
      $user->updatePassword($password);
      $user->is_authenticated = true;
      $user->last_login = time();
      $user->last_login_ip = $_SERVER['REMOTE_ADDR'];
      $_SESSION['username'] = $fio_address;
      $_SESSION["session_start"] = time();
      $user->save();
      header("Location: index.php");
    } else {
      $errorString = "Passwords do not match. Please try again.";
    }
  }
}

if (isset($_POST["next_action"]) && $_POST["next_action"] == "check_login") {
  if ($fio_address == "" || $password == "") {
    $errorString = "Required fields missing.";
  }
  if ($errorString == "") {
    $user = new User($fio_address);
    $authenticated = $user->login($password);
    if (!$authenticated) {
      $errorString = "Incorrect login information.";
    }
  }
  if ($errorString == "" && $authenticated) {
    $_SESSION['username'] = $fio_address;
    $_SESSION["auto_play"] = $user->auto_play;
    $_SESSION["session_start"] = time();
    header("Location: index.php");
  }
}
?><!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">

  <title>Wisdom Nuggets! Memorize Cool Sh*t</title>
  </head>
  <body>
    <div class="container-fluid">

      <?php
      $action = "login";
      $next_action = "check_login";
      if (isset($_GET["action"]) && $_GET["action"] == "register") {
        $action = "register";
        $next_action = "save_login";
      }

      if ($errorString != "") {
        print "<div class=\"alert alert-danger\" role=\"alert\">" . $errorString . "</div>";
      }
      ?>

      <form action="login_and_register.php" method="POST">
        <div class="form-group">
          <label for="fio_address"><a href="https://fioprotocol.io/free-fio-addresses/">FIO Address</a></label>
          <input type="text" class="form-control" id="fio_address" name="fio_address" aria-describedby="fio_addressHelp" placeholder="Enter FIO Address" value="<?php print $fio_address; ?>">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Password" value="<?php print $password; ?>">
        </div>
        <?php if ($action == "register") { ?>
        <div class="form-group">
          <label for="password_again">Password Again</label>
          <input type="password" class="form-control" id="password_again" name="password_again" placeholder="Password Again" value="<?php print $password_again; ?>">
        </div>
        <?php } ?>
        <input type="hidden" name="next_action" value="<?php print $next_action; ?>">
        <button type="submit" class="btn btn-primary"><?php print ucwords($action); ?></button>
        <?php if ($action == "login") { ?>
          <a class="btn btn-primary" href="login_and_register.php?action=register" role="button">Register</a>
        <?php } ?>
      </form>

      <a href="/">Home</a>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs="crossorigin="anonymous"></script>
  </body>
</html>