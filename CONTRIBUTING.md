# POData-Laravel Contributor's Guide

Contribution policies, workflows, etc

**Development practice**

* **Bring your work up to date**
Merging changes based on old code is painful and annoying, at best.
Please resolve merge conflicts and the like by rebasing atop the master
HEAD commit.

As you rebase, please roll in typo-fix commits, whoops-forgot-this commits,
and the like to the commit that gave rise to them.

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

* **Pay attention to Scrutinizer results**
Static analysis is another effective method to reduce bugs, so we use it
on this project.  We've hooked up Scrutinizer as part of our CI process
and have found that it both finds bugs directly, and makes other bugs
easier to find.

Please don't introduce any new critical-severity issues.

Likewise, please don't introduce any classes or methods that Scrutinizer rates
F (D, C, B and A are fine).

We've found, from bitter experience, that F-rated classes are very difficult
to understand, and if it can't be understood, it can't be reviewed.  If it
can't be reviewed, it's not going to be merged.

For every new issue you end up introducing, please ensure that you
resolve at least two others.

Yes, this makes it easier (to get your changes approved) to fix issues
as you go, rather than later.  This is deliberate.

* **Eat.  Sleep.  PSR-2.  Repeat.**
As POData-Laravel is a Composer package, we apply the PSR-2 coding
standard.  Code formatted to one standard is a heck of a lot easier to
read than code formatted to a jumble of "standards" - for instance,
pre-Heartbleed OpenSSL.

