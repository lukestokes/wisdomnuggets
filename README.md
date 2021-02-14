# Wisdom Nuggets
Playing around with interactive games for increasing wisdom.

Ideas for improvement:

* DONE: Add Ray Dalio Principles: https://www.nateliason.com/notes/principles-ray-dalio
* Add instructions for anyone to include pull requests for any chunk of wisdom nuggets they want to memorize.
* Keep track of correct answers (browser storage only or add a server-side database?)
* DONE: Track answers over time (gamify daily activity)
* Let the user tag nuggets which are hard to remember which increases the frequency they are shown
* Let the user remove a nugget so it won't display for them anymore
* DONE: Split phrases accoring to the size of the phrase. Right now, it just a random selection each time of 3 to 5 words in each group. Small phrases should allow for single word groupings. Large phrases should have larger word groups. Might also want to split things up differently so that last group can't come out smaller.
* Consider a "hard" mode which doesn't use the color pattern hints
* Allow users to star items as their favorites, aggregate the data and show number of favorites.
* Move user storage stuff from flat files to a database or something like https://sleekdb.github.io/

Block Chain Integration Ideas:

* DONE: Login with FIO
* Randomized rewards. Prompt to send a FIO request with a very specific code in the memo. A lot of thought has to go into this in terms of how to prevent bot attacks. Various ideas include captcha, anaylzing time between clicks, blacklisting FIO addresses which abuse the system, checking against IPs, geo-location fencing, and more.
* (?) xOnly allow usage via a FIO address that is paid for (integrate with the FIO address registration site)
* Play to a certain point until they earn a FIO address.
* Use Anchor to generate a FIO public / private key pair (instructions on how to do this)


