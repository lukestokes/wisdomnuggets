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
$Nugget = $Wisdom->getRandom($type, $category);
$display = (($Nugget->title == "") ? $Nugget->category : $Nugget->title);
$display = ucwords($Nugget->type) . ":  " . $display;
$grouped_words = $Nugget->split(3,5);

?>
<!DOCTYPE HTML>
<html>
<head>
<style>
.box {
  width: 800px;
  height: 150px;
  padding: 10px;
  border: 1px solid #aaaaaa;
}
.group {
  padding: 3px;
  margin: 1px;
  line-height: 30px;
  word-wrap: normal;
  display: inline-block;  
}
/* https://coolors.co/palettes/trending */
.color0 {
  border: 3px solid #264653;
}
.color1 {
  border: 3px solid #2A9D8F;
}
.color2 {
  border: 3px solid #E9C46A;
}
.color3 {
  border: 3px solid #F4A261;
}
.color4 {
  border: 3px solid #F4A261;
}

</style>
<script>

function move(ev, where) {
	ev.preventDefault();
	document.getElementById(where).appendChild(ev.target);
	if (where == "destination" && document.getElementById("source").children.length == 0) {
		checkAnswer();
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
}

function showAnswer(as_correct) {
	if (as_correct) {
		document.getElementById('solution').style.border = "2px solid green";
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
	location.replace(base + "?type_category=" + type_category);
}
</script>
</head>
<body>

<h1><?php print $display; ?></h1>

<p>Click or drag the grouped words in the correct order.</p>

<div class="box" id="source" onclick="move(event,'destination')" ondrop="drop(event)" ondragover="allowDrop(event)">
<?php
$group_spans = array();
foreach ($grouped_words as $key => $group) {
	$group_spans[] = "<span class=\"group color" . ($key % 5) . "\" id=\"drag" . $key . "\" draggable=\"true\" ondragstart=\"drag(event)\">" . $group . "</span>";
}
shuffle($group_spans);
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

Filter for: <select name="type_category" id="type_category" onchange="updateLocation();">
<?php $Wisdom->printTypeCategoryOptions($type_category); ?>
</select>

<p>Credit for these Maxims goes to <a href="https://seanking.substack.com/p/the-maxims">Sean King's blog</a></p>
<p>Credit Logical Fallacies goes to <a href="https://yourlogicalfallacyis.com/">yourlogicalfallacyis.com/</a></p>
<?php $Wisdom->printStats(); ?>

</body>
</html>
