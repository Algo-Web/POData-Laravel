Unreleased
----------
   * Add capability to use Laravel's passport authentication (if available)
        Thank you to Renan William Alves de Paula
        (renan@4success.com.br) for the patch
   * Add functionality to customise endpoint names
   * Only squash cli provider exceptions
   * Handle Laravel accessor methods (getFooAttribute)

0.2.4 (2016-12-27)
------------------
   * Squash exceptions during class enumeration, so your application can
   still boot (and migrate, handle request, bust out the Melbourne
   Shuffle, etc)

0.2.3 (2016-12-26)
------------------
   * Work around Doctrine/MariaDB glitch where tilde characters end up
   appended to column names
   * Relax namespace requirements on controller trait - so you can use
   POData-Laravel in other Laravel packages as well as directly

0.2.2 (2016-12-25)
------------------
   * Allow metadata controller hookup to work - feed actual controllers,
   not just names, into metadata controller provider

0.2.1 (2016-12-24)
------------------
   * Stop preventing migrations when metadata trait enabled

0.2.0 (2016-12-22)
------------------
   * Pick up stable version of POData

0.1.1 (2016-11-04)
------------------
    
   * Stop glomming onto home route.

0.1.0 (2016-11-03)
------------------

   * Initial release.
