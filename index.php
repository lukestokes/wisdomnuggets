<?php
include "objects.php";
include "wisdom_data.php";

$type = "";
$category = "";
$type_category = "";
if (isset($_GET["type_category"]) && $_GET["type_category"] != "") {
    $type_category = strip_tags($_GET["type_category"]);
    $values = explode("|", $type_category);
    $type = $values[0];
    $category = $values[1];
}
foreach ($Wisdom->chunks as $chunk) {
    if (isset($_GET["exclude_chunk_" . $chunk->type]) && $_GET["exclude_chunk_" . $chunk->type] == "on") {
        $chunk->included = false;
    }
}
$Nugget = $Wisdom->getRandom($type, $category);
$display = (($Nugget->title == "") ? $Nugget->category : $Nugget->title);
$display = ucwords($Nugget->type) . ":  " . $display;
$grouped_words = $Nugget->split(3,5);

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
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
  <title>Wisdom Nuggets! Memorize Cool Sh*t</title>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row m-3">
      <h4>Wisdom Nuggets: Grow your mind for fun and profit.</h4>
      <p>Click the grouped words in the correct order to complete the phrase.</p>
      <div class="card">
        <div class="card-body">
          <h2 class="card-title"><?php print $display; ?></h2>
          <div class="card">
            <div class="card-body">
              <div class="btn-toolbar" id="source" onclick="move(event,'destination')">
                <?php
                //$classes = array('primary','secondary','success','warning','info');
                $group_buttons = array();
                foreach ($grouped_words as $key => $group) {
                    $group_buttons[] = "<button type=\"button\" class=\"btn btn-outline-" . $button_classes[$key % count($button_classes)] . " mx-1\" id=\"drag" . $key . "\" draggable=\"true\" ondragstart=\"drag(event)\">" . $group . "</button>\n";
                }
                $original_group_buttons = $group_buttons;
                shuffle($group_buttons);
                if ($original_group_buttons == $group_buttons) {
                    shuffle($group_buttons); // although the original order is a valid random shuffle, it still "feels" broken
                }
                foreach ($group_buttons as $key => $button) {
                    print $button;
                }
                ?>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-body">
              <div class="btn-toolbar" id="destination" onclick="move(event,'source')">
              </div>
            </div>
          </div>
          <div id="solution" style="display:none;" class="alert" role="alert"><?php print $Nugget->description; ?></div>
          <button type="button" class="btn btn-info btn-sm" onclick="showAnswer(false);">Show Answer</button>
        </div>
      </div>
      <div class="row">
        <button type="button" class="btn btn-primary" onclick="location.reload();">Play Again!</button>
        <div class="card">
          <div class="card-body">
            <div class="form-floating">
              <select name="type_category" class="form-select" id="type_category" aria-label="Filter for:" onchange="updateLocation();">
                <?php $Wisdom->printTypeCategoryOptions($type_category); ?>
              </select>
              <label for="type_category">Filter for:</label>
            </div>
            <p class="card-title">Exclude:</p>
            <?php $Wisdom->printChunkCheckboxes(); ?>
          </div>
        </div>
      </div>
      <p><sub>Code lives <a href="https://github.com/lukestokes/wisdomnuggets">here</a></sub></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs="crossorigin="anonymous"></script>
    <script src="js/wisdom_nuggets.js"></script>
    <script>
    function updateLocation() {
        var e = document.getElementById("type_category");
        var type_category = e.value;
        var base = window.location.href.split('?')[0];
        var new_url = base + "?type_category=" + type_category;
        <?php
        foreach ($Wisdom->chunks as $chunk) {
            print "if (document.getElementById(\"exclude_chunk_" . $chunk->type . "\").checked) {";
            print "new_url += \"&exclude_chunk_" . $chunk->type . "=on\";";
            print "}";
        }
        ?>
        location.replace(new_url);
    }
    </script>
  </body>
</html>