<?php
require_once __DIR__ . '/vendor/autoload.php';
$client = new GuzzleHttp\Client(['base_uri' => 'http://fio.greymass.com']);
include "header.php";
?>
<!doctype html>
<html lang="en">
  <head>
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
  <body onload="restoreSession()">
    <div class="container-fluid">
      <div class="row m-3">
        <?php if ($_SESSION["completed"] < 2 || !isset($_SESSION['username'])) { ?>
        <h4><?php print $title; ?></h4>
        <?php } ?>
        <div>
          <?php if ($_SESSION["completed"] < 2 || !isset($_SESSION['username'])) { ?>Click the grouped words in the correct order to complete the phrase. <a data-toggle="collapse" href="#whyText">Why?</a><?php } ?> <?php print $login_status_string; ?>
        </div>
        <div class="collapse" id="whyText">
          <div class="alert alert-primary" role="alert"><?php print $description . ' You can also win some cryptocurrency for playing if you ' . $onboarding_pitch; ?></div>
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
              <input type="hidden" id="actor" name="actor" value="">
              <input type="hidden" id="identity_proof" name="identity_proof" value=''>
              <div class="form-group">
                <button type="submit" class="btn btn-primary">Play Again!</button>
              </div>
              <?php if (isset($_SESSION['username'])) { ?>
              <div>
                [<a data-toggle="collapse" href="#settings">settings</a>]
              </div>
              <div class="collapse" id="settings">
              <?php } ?>
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
              <?php if (isset($_SESSION['username'])) { ?>
              </div>
              <?php } ?>
              <input type="hidden" id="previous_answers" name="previous_answers" value="">
            </form>
          </div>
        </div>

        <p>You've completed <?php print $_SESSION["completed"]; ?> this session. [<a href="?save">save stats</a>] [<a href="?viewStats">view stats</a>]</p>
        <?php
        $Faucet = new Faucet($client);
        if (isset($_SESSION['username'])) {
          if (isset($_GET["viewStats"])) {
            print "<p>";
            $user = new User($_SESSION['username']);
            $user->read();
            $user->showSessions();
            print "</p>";
          }
          print "<h3>Your Recent Faucet Rewards</h3>";
          $payments = $Faucet->getPayments(["actor","=",$_SESSION['username']], 10);
          print "<p>";
          $Faucet->printPayments($payments);
          print "</p>";

        }
        print "<h3>Recent Faucet Rewards</h3>";
        $all_payments = $Faucet->getPayments(["status","=","Paid"], 10);
        print "<p>";
        $Faucet->printPayments($all_payments);
        print "</p>";
        ?>
        <a href="https://pixabay.com/vectors/owl-reading-book-bird-study-4783407/"><img src="<?php print $image; ?>" class="img-fluid"/></a>
        <script>
        // load facebook share features
        (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.0";
        fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));

        // load Twiter share features
        window.twttr = (function(d, s, id) {
          var js, fjs = d.getElementsByTagName(s)[0],
            t = window.twttr || {};
          if (d.getElementById(id)) return t;
          js = d.createElement(s);
          js.id = id;
          js.src = "https://platform.twitter.com/widgets.js";
          fjs.parentNode.insertBefore(js, fjs);

          t._e = [];
          t.ready = function(f) {
            t._e.push(f);
          };
          return t;
        }(document, "script", "twitter-wjs"));
        </script>
        <div class="row align-items-center">
          <!-- Your share button code -->
          <div class="col">
            <div class="fb-share-button" data-href="https://wisdomnuggets.lukestokes.info/" data-layout="button_count"></div>
          </div>
          <div class="col">
            <a class="twitter-hashtag-button" href="https://twitter.com/intent/tweet?" data-hashtags="wisdomnuggets" data-text='Come play with some wisdom nuggets! Have fun and win crypto programming your brain @WiseFIOFaucet' data-url="https://wisdomnuggets.lukestokes.info" target="_blank">Tweet</a>
          </div>
        </div>

        <p><sub>You can view the code for this and <a href="https://github.com/lukestokes/wisdomnuggets">find out more here</a>. Or meditate with a <a href="https://sri-yantra.lukestokes.info/">Sri Yantra</a></sub></p>

      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script>
    var auto_play = <?php print $auto_play; ?>;
    var completed = false;
    <?php if (isset($_GET{'login'})) { ?>
      $(function() {
        login();
      });
    <?php } ?>
    <?php if (isset($_GET{'logout'})) { ?>
      $(function() {
        logout();
      });
    <?php } ?>
    </script>
    <script src="https://unpkg.com/anchor-link@3"></script>
    <script src="https://unpkg.com/anchor-link-browser-transport@3"></script>
    <script src="js/wisdom_nuggets.js?v=3"></script>
    <script>
    // app identifier, should be set to the eosio contract account if applicable
    const identifier = 'wisdomnuggets'
    // initialize the browser transport
    const transport = new AnchorLinkBrowserTransport()
    // initialize the link
    const link = new AnchorLink(
        {
          transport,
          chains: [
              {
                  chainId: '21dcae42c0182200e93f954a074011f9048a7624c6fe81d3c9541a614a88bd1c',
                  nodeUrl: 'https://fio.greymass.com',
              }
          ],
        }
      );
    // the session instance, either restored using link.restoreSession() or created with link.login()
    let session
    </script>
  </body>
</html>