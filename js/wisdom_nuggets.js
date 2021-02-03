
$(function() {
    setTimeout(function() {
        jQuery("#destination_container").show();
        jQuery("#source_container").show();
    }, 1000);
});

function move(ev, where) {
    if (ev.target.id != "destination" && ev.target.id != "source" && !completed) {
        ev.preventDefault();
        document.getElementById(where).appendChild(ev.target);
        if (where == "destination" && document.getElementById("source").children.length == 0) {
            checkAnswer();
        }
    }
}

function showAnswer(as_correct) {
    if (as_correct) {
        completed = true;
        jQuery("#solution").addClass("alert-success");

        characters_to_share = 180;
        share_text = jQuery("#solution").html().substring(0,characters_to_share);
        if (jQuery("#solution").html().length > characters_to_share) {
            share_text += "...";
        }
        share_button = "<br /><a class=\"twitter-hashtag-button\" href=\"https://twitter.com/intent/tweet?\" data-hashtags=\"wisdomnuggets\" data-text='\"" + share_text + "\"' data-url=\"http://wisdomnuggets.lukestokes.info\" target=\"_blank\">Tweet</a>";
        jQuery("#solution").append(share_button);
        document.getElementById('destination').innerHTML = "";
        document.getElementById('destination').appendChild(document.getElementById('solution'));
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