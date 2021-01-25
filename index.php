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
		'e9fff9',
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
		'e9ff70',
	),
);

$key = array_rand($color_schemes);
$color_scheme = $color_schemes[$key];

?>
<!DOCTYPE HTML>
<html>
<head>
<style>
p, span, label {
  font-size: 20px;
}
.box {
  width: 800px;
  height: 170px;
  padding: 10px;
  border: 1px solid #aaaaaa;
}
.group {
  padding: 3px;
  margin: 1px;
  line-height: 28px;
  word-wrap: normal;
  display: inline-block;  
}
/* https://coolors.co/palettes/trending */
<?php
foreach ($color_scheme as $key => $value) {
print '.color' . $key . ' {
  border: 6px solid #' . $value . ';
}';
}
?>

</style>
<script>

function move(ev, where) {
	if (ev.target.id != "destination" && ev.target.id != "source") {
		ev.preventDefault();
		document.getElementById(where).appendChild(ev.target);
		if (where == "destination" && document.getElementById("source").children.length == 0) {
			checkAnswer();
		}
	}
}

function allowDrop(ev) {
	ev.preventDefault();
}

function drag(ev) {
	ev.dataTransfer.setData("text", ev.target.id);
}

function drop(ev) {
	ev.preventDefault();
	var data = ev.dataTransfer.getData("text");
	ev.target.appendChild(document.getElementById(data));
	if (ev.target.id == "destination" && document.getElementById("source").children.length == 0) {
		checkAnswer();
	}
}

function showAnswer(as_correct) {
	if (as_correct) {
		document.getElementById('solution').style.border = "2px solid green";
		document.getElementById('destination').innerHTML = "";
		document.getElementById('destination').appendChild(document.getElementById('solution'));
	}
	document.getElementById('solution').style.display = 'block';
}

function checkAnswer() {
	var answerIsCorrect = true;
	var currentGroup = 0;
	var answerGiven = document.getElementById("destination");
	if (answerGiven.children.length == 0) {
		answerIsCorrect = false;
	}
	if (document.getElementById("source").children.length != 0) {
		answerIsCorrect = false;
	}
	for (var i = 0; i < answerGiven.children.length; i++) {
	  var answer = answerGiven.children[i].id.replace("drag", "");
	  if (answer != i) {
	  	answerIsCorrect = false;
	  }
	}
	var check_solution_result = "::: Incorrect: Please Try Again :::";
	var result_color = "red";
	if (answerIsCorrect) {
		check_solution_result = "Correct!";
		result_color = "green";
		showAnswer(true);
	}
	document.getElementById("check_solution_result").innerHTML = check_solution_result;
	document.getElementById("check_solution_result").style.color = result_color;
}

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
</head>
<body>
<p>Click or drag the grouped words in the correct order.</p>
<hr />
<h1><?php print $display; ?></h1>
<div class="box" id="source" onclick="move(event,'destination')" ondrop="drop(event)" ondragover="allowDrop(event)">
<?php
$group_spans = array();
foreach ($grouped_words as $key => $group) {
	$group_spans[] = "<span class=\"group color" . ($key % 5) . "\" id=\"drag" . $key . "\" draggable=\"true\" ondragstart=\"drag(event)\">" . $group . "</span>";
}
$original_group_spans = $group_spans;
shuffle($group_spans);
if ($original_group_spans == $group_spans) {
	shuffle($group_spans); // although the original order is a valid random shuffle, it still "feels" broken
}
foreach ($group_spans as $key => $span) {
	print $span;
}
?>
</div>
<br>
<div class="box" id="destination" onclick="move(event,'source')" ondrop="drop(event)" ondragover="allowDrop(event)"></div>

<div style="width: 800px;">
	<p id="solution" style="display:none; padding:10px;"><strong><?php print $Nugget->description; ?></strong></p>
</div>
<button style="padding: 15px;" onclick="showAnswer(false);">Show Answer</button> <span id="check_solution_result"></span>

<br /><br />
<button style="padding: 15px;" onclick="location.reload();">Play Again!</button>

<label>Filter for: </label><select name="type_category" id="type_category" onchange="updateLocation();">
<?php $Wisdom->printTypeCategoryOptions($type_category); ?>
</select>

<br/><br/><br/><br/><br/><br/>
<p>Exclude:</p>
<?php $Wisdom->printChunkCheckboxes(); ?>
<p><sub>Code lives <a href="https://github.com/lukestokes/wisdomnuggets">here</a></sub></p>

</body>
</html>
