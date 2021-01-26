
$(function() {
    setTimeout(function() {
        jQuery("#destination_container").show();
        jQuery("#source_container").show();
    }, 2000);
});

function move(ev, where) {
    if (ev.target.id != "destination" && ev.target.id != "source") {
        ev.preventDefault();
        document.getElementById(where).appendChild(ev.target);
        if (where == "destination" && document.getElementById("source").children.length == 0) {
            checkAnswer();
        }
    }
}

function showAnswer(as_correct) {
    if (as_correct) {
        jQuery("#solution").addClass("alert-success");
        document.getElementById('destination').innerHTML = "";
        document.getElementById('destination').appendChild(document.getElementById('solution'));
        if (auto_play) {
            var timeleft = auto_play;
            var reloadTimer = setInterval(function(){
              if(timeleft <= 0){
                clearInterval(reloadTimer);
              }
              document.getElementById("progressBar").value = auto_play - timeleft;
              timeleft -= .5;
            }, 500);
            setTimeout(function() {
                jQuery("#main_form").submit();
            }, auto_play * 1000);
        }
    }
    jQuery("#solution").show();
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
    if (answerIsCorrect) {
        showAnswer(true);
    }
}