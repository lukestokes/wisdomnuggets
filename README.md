# Wisdom Nuggets

## Wisdom Nuggets! Have Fun and Earn Crypto Programming Your Brain

Playing around with interactive games for increasing wisdom.

Reality is the result of our actions initiated by our thoughts. Have fun playing a game to help you memorize key principles, maxims, logical fallacies, and more for clear thinking and success. To improve the world, start with yourself.

Why not also earn some cryptocurrency in the process? I've set up this little game, currently available at http://wisdomnuggets.lukestokes.info/

This is my own personal project using my own time and funds. If you want to donate to it's development, you can send to luke@stokes and if you want to contribute FIO tokens to the faucet, you can send those to faucet@stokes.


### Ideas for improvement:

* Add instructions for anyone to include pull requests for any chunk of wisdom nuggets they want to memorize.
* Keep track of correct answers (browser storage only or add a server-side database?)
* Let the user tag nuggets which are hard to remember which increases the frequency they are shown
* Let the user remove a nugget so it won't display for them anymore
* Consider a "hard" mode which doesn't use the color pattern hints
* Allow users to star items as their favorites, aggregate the data and show number of favorites.

* DONE: Split phrases accoring to the size of the phrase. Right now, it just a random selection each time of 3 to 5 words in each group. Small phrases should allow for single word groupings. Large phrases should have larger word groups. Might also want to split things up differently so that last group can't come out smaller.
* DONE: Track answers over time (gamify daily activity)
* DONE: Move user storage stuff from flat files to a database or something like https://sleekdb.github.io/
* DONE: Add Ray Dalio Principles: https://www.nateliason.com/notes/principles-ray-dalio

#### Block Chain Integration Ideas:

* Prompt to send a FIO request with a very specific code in the memo. A lot of thought has to go into this in terms of how to prevent bot attacks. Various ideas include captcha, anaylzing time between clicks, blacklisting FIO addresses which abuse the system, checking against IPs, geo-location fencing, and more.
* (?) Only allow usage via a FIO address that is paid for (integrate with the FIO address registration site)
* Play to a certain point until they earn a FIO address.
* Use Anchor to generate a FIO public / private key pair (instructions on how to do this)
* Show FIO earned per session?
* DONE: Limit rewards per account by time period?
* DONE: Login with FIO
* DONE: Randomized rewards.
* DONE: Build backend admin tool that processes transactions.

### Giveaway Improvement Ideas

How to determine a person?

"You are 0% human..."

Ways to increase humanness:

* Connect with Keybase
* Connect with Facebook
* Connect with Twitter
* Connect with Idena
* Use a FIO Address on a domain that isn’t part of the giveaway process.

