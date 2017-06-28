# POData-Laravel Contributor's Guide

Contribution policies, workflows, etc

**Development practice**

* **Don't break the project**
If the Travis build doesn't pass cleanly, that's broken enough for us.
The PHP-nightly-version builds are permitted to fail - when someone manages
to drop the blocker out, they'll resume their early-warning role.

* **Keep each pull request to one piece of conceptual work at a time**
You can work on as many different features/bugfixen as you like, but one
pull request for each, please.

* **A given patch needs to fit comfortably in a single reviewer's head**
Easy to review means easy to find and fix bugs before they land in
mainline.  Easy to review also means easy to approve and merge.

* **Test your work**
Changing code?  Give us tests that show you've thought about your change,
tested it, etc - and help us automatically check your changes.  If you
find (and fix) bugs in your change before we see it, so much the better.

* **Don't break any existing tests**
Every test that passed before your patch must pass afterwards.

* **Don't reduce coverage level**
High code coverage drives critical code review and shakes lots of bugs
out.  We've found keeping coverage high (95% or better) to be a very
effective way of keeping the dev train steaming onwards at full throttle
with a low bug load.  Since we've all done it, you need to do it as well.

* **Eat.  Sleep.  PSR-2.  Repeat.**
As POData-Laravel is a Composer package, we apply the PSR-2 coding
standard.  Code formatted to one standard is a heck of a lot easier to
read than code formatted to a jumble of "standards" - for instance,
pre-Heartbleed OpenSSL.

