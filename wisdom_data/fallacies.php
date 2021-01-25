<?php

$LogicalFallacies = new Chunk("fallacy","Logical Fallacies","https://yourlogicalfallacyis.com");

$category = "logic";
$LogicalFallacies->addNugget($category, "You misrepresented someone's argument to make it easier to attack.","Strawman");
$LogicalFallacies->addNugget($category, "You assumed that one part of something has to be applied to all, or other, parts of it; or that the whole must apply to its parts.","Composition / Division");
$LogicalFallacies->addNugget($category, "You used a personal experience or an isolated example instead of a sound argument or compelling evidence.","Anecdotal");
$LogicalFallacies->addNugget($category, "You attempted to manipulate an emotional response in place of a valid or compelling argument.","Appeal to Emotion");
$LogicalFallacies->addNugget($category, "You avoided having to engage with criticism by turning it back on the accuser - you answered criticism with criticism.","Tu Quoque");
$LogicalFallacies->addNugget($category, "You appealed to popularity or the fact that many people do something as an attempted form of validation.","Bandwagon");
$LogicalFallacies->addNugget($category, "You judged something as either good or bad on the basis of where it comes from, or from whom it came.","Genetic");
$LogicalFallacies->addNugget($category, "You argued that because something is 'natural' it is therefore valid, justified, inevitable, good or ideal.","Appeal to Nature");
$LogicalFallacies->addNugget($category, "You said that if we allow A to happen, then Z will eventually happen too, therefore A should not happen.","Slippery Slope");
$LogicalFallacies->addNugget($category, "You made what could be called an appeal to purity as a way to dismiss relevant criticisms or flaws of your argument.","No True Scotsman");
$LogicalFallacies->addNugget($category, "You claimed that a compromise, or middle point, between two extremes must be the truth.","Middle Ground");
$LogicalFallacies->addNugget($category, "You presumed that because a claim has been poorly argued, or a fallacy has been made, that the claim itself must be wrong.","The Fallacy Fallacy");
$LogicalFallacies->addNugget($category, "Because you found something difficult to understand, or are unaware of how it works, you made out like it's probably not true.","Personal Incredulity");
$LogicalFallacies->addNugget($category, "You used a double meaning or ambiguity of language to mislead or misrepresent the truth.","Ambiguity");
$LogicalFallacies->addNugget($category, "You presented two alternative states as the only possibilities, when in fact more possibilities exist.","Black-or-White");
$LogicalFallacies->addNugget($category, "You attacked your opponent's character or personal traits in an attempt to undermine their argument.","Ad Hominem");
$LogicalFallacies->addNugget($category, "You asked a question that had a presumption built into it so that it couldn't be answered without appearing guilty.","Loaded Question");
$LogicalFallacies->addNugget($category, "You cherry-picked a data cluster to suit your argument, or found a pattern to fit a presumption.","The Texas Sharpshooter");
$LogicalFallacies->addNugget($category, "You moved the goalposts or made up an exception when your claim was shown to be false.","Special Pleading");
$LogicalFallacies->addNugget($category, "You said that 'runs' occur to statistically independent phenomena such as roulette wheel spins.","The Gambler's Fallacy");
$LogicalFallacies->addNugget($category, "You presented a circular argument in which the conclusion was included in the premise.","Begging the Question");
$LogicalFallacies->addNugget($category, "You said that the burden of proof lies not with the person making the claim, but with someone else to disprove.","Burden of Proof");
$LogicalFallacies->addNugget($category, "You presumed that a real or perceived relationship between things means that one is the cause of the other.","False Cause");
$LogicalFallacies->addNugget($category, "You said that because an authority thinks something, it must therefore be true.","Appeal to Authority");

$Wisdom->addChunk($LogicalFallacies);
