<?php
include "header.php";

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
  foreach ($Wisdom->chunks as $chunk) {
    $chunk->included = false;
    if (isset($_GET["include_chunk_" . $chunk->type]) && $_GET["include_chunk_" . $chunk->type] == "on") {
      $chunk->included = true;
    }
  }
}

if (!isset($_SESSION["completed"])) {
  $_SESSION["completed"] = 0;
}

if (isset($_SESSION["previous_answers"]) && isset($_GET["previous_answers"])) {
  if ($_SESSION["previous_answers"] == $_GET["previous_answers"] && $_GET["previous_answers"] != "") {
    $_SESSION["completed"]++;
  }
}

$Nugget = $Wisdom->getRandom($type, $category);
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
        '264653',
        '2A9D8F',
        'E9C46A',
        'F4A261',
        'F4A261',
    ),
    2 => array(
        'd64045',
        '87ccb9',
        '9ed8db',
        '467599',
        '1d3354',
    ),
    3 => array(
        'd7263d',
        'f46036',
        '2e294e',
        '1b998b',
        'c5d86d',
    ),
    4 => array(
        '70d6ff',
        'ff70a6',
        'ff9770',
        'ffd670',
        'abba56',
    ),
);

$key = array_rand($color_schemes);
$color_scheme = $color_schemes[$key];

?>

<!doctype html>
<html lang="en">
  <head>
    <?php
      $title = "Wisdom Nuggets! Have Fun Programming Your Brain";
      $description = "Reality is the result of our actions initiated by our thoughts. Have fun playing a game to help you memorize key principles, maxims, logical fallacies, and more for clear thinking and success. To improve the world, start with yourself.";
      $image = "https://wisdomnuggets.lukestokes.info/images/sriyantra.png";
      $url = "https://wisdomnuggets.lukestokes.info/";
    ?>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="<?php print $description; ?>">
    <meta name="author" content="Luke Stokes">
    <link rel="icon" href="favicon.ico" type="image/x-icon" />

    <!-- Facebook -->
    <meta property="og:url"           content="<?php print $url; ?>" />
    <meta property="og:type"          content="website" />
    <meta property="og:title"         content="<?php print $title; ?>" />
    <meta property="og:description"   content="<?php print $description; ?>" />
    <meta property="og:image"         content="<?php print $image; ?>" />

    <!-- Twitter -->
    <meta name="twitter:creator" content="@lukestokes">
    <meta name="twitter:title" content="<?php print $title; ?>">
    <meta name="twitter:description" content="<?php print $description; ?>">
    <meta name="twitter:image" content="<?php print $image; ?>">


    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">

<style>
/* https://coolors.co/palettes/trending */
<?php
$button_classes = array('primary','secondary','success','warning','info');
foreach ($color_scheme as $key => $value) {
print '.btn-outline-' . $button_classes[$key] . ' {
  color: #' . $value . ';
  border-color: #' . $value . ';
}';
print '.btn-outline-' . $button_classes[$key] . ':hover, .btn-outline-' . $button_classes[$key] . ':focus, .btn-outline-' . $button_classes[$key] . ':active  {
  color: #FFFFFF;
  background-color: #' . $value . ';
  border-color: #000000;
}';

print '.btn-' . $button_classes[$key] . ' {
  color: #FFFFFF;
  background-color: #' . $value . ';
}';
print '.btn-' . $button_classes[$key] . ':hover, .btn-' . $button_classes[$key] . ':focus, .btn-' . $button_classes[$key] . ':active  {
  color: #FFFFFF;
  background-color: #' . $value . ';
  border-color: #000000;
}';
}
?>

</style>
  <title><?php print $title; ?></title>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row m-3">
        <h4><?php print $title; ?></h4>
        <p>Click the grouped words in the correct order to complete the phrase. <a data-toggle="collapse" href="#whyText">Why?</a></p>
        <div class="collapse" id="whyText">
          <div class="alert alert-primary" role="alert"><?php print $description; ?></div>
        </div>
        <div class="card">
          <div class="card-body">
            <sub><?php print ucwords($Nugget->type); ?></sub>
            <h4 class="card-title"><?php print $display; ?></h4>
            <div class="card" id="source_container" style="display: none;">
              <div class="card-body">
                <div class="btn-toolbar" id="source" onclick="move(event,'destination')">
                  <?php
                  //$classes = array('primary','secondary','success','warning','info');
                  $group_buttons = array();
                  $original_button_string = "";
                  foreach ($grouped_words as $key => $group) {
                    $button = "<button type=\"button\" class=\"btn btn-outline-" . $button_classes[$key % count($button_classes)] . " mx-1\" id=\"button" . $key . "\">" . $group . "</button>";
                    $group_buttons[] = $button;
                    $original_button_string .= $button;
                  }
                  $original_group_buttons = $group_buttons;
                  shuffle($group_buttons);
                  if ($original_group_buttons == $group_buttons) {
                    shuffle($group_buttons); // although the original order is a valid random shuffle, it still "feels" broken
                  }
                  $_SESSION["previous_answers"] = md5($original_button_string);
                  foreach ($group_buttons as $key => $button) {
                    print $button;
                  }
                  ?>
                </div>
              </div>
              <div class="card" id="destination_container" style="display: none;">
                <div class="card-body">
                  <div class="btn-toolbar" id="destination" onclick="move(event,'source')">
                  </div>
                </div>
              </div>
            </div>
            <div id="solution" style="display:none;" class="alert" role="alert"><?php print $Nugget->description; ?></div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="showAnswer(false);">Show Answer</button>
          </div>
          <div class="row">
            <?php
            if ($auto_play) {
              print "<progress value=\"0\" max=\"" . ($auto_play-1) . "\" id=\"progressBar\"></progress>";
            }
            ?>
            <form method="GET" id="main_form">
              <input type="hidden" name="customized" value="true">
              <div class="form-group">
                <button type="submit" class="btn btn-primary">Play Again!</button>
              </div>
              <div class="form-floating">
                <select name="auto_play" class="form-select" id="auto_play">
                  <?php $Wisdom->printAutoplayOptions($auto_play); ?>
                </select>
                <label for="auto_play">Autoplay after:</label>
              </div>
              <div class="form-floating">
                <select name="type_category" class="form-select" id="type_category">
                  <?php $Wisdom->printTypeCategoryOptions($type_category); ?>
                </select>
                <label for="type_category">Filter for:</label>
              </div>
              <div class="form-group">
                <p class="card-title">Include: </p>
                <?php $Wisdom->printChunkCheckboxes(); ?>
              </div>
              <input type="hidden" id="previous_answers" name="previous_answers" value="">
            </form>
          </div>
        </div>
        <p><sub>Code lives <a href="https://github.com/lukestokes/wisdomnuggets">here</a>. <a href="https://sri-yantra.lukestokes.info/">Sri Yantra</a>, anyone?</sub></p>

        <p>You've completed <?php print $_SESSION["completed"]; ?> this session. <a href="?save">Save your stats</a>.</p>
        <?php
        print $login_status_string;
        if (isset($_GET["viewStats"]) && isset($_SESSION['username'])) {
          print "<p>";
          $user = new User($_SESSION['username']);
          $user->read();
          $user->showSessions();
          print "</p>";
        }
        ?>

        <script>
        // load facebook share features
        (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.0";
        fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
        </script>
        <!-- Your share button code -->
        <div class="fb-share-button" data-href="https://wisdomnuggets.lukestokes.info/" data-layout="button_count"></div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script>
    var auto_play = <?php print $auto_play; ?>;
    var completed = false;
    </script>
    <script src="js/wisdom_nuggets.js"></script>
  </body>
</html>