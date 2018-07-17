0.3.5 (2018-07-17)
------------------
   * Make README hopefully less confusing
     - Thank you to donmbelembe for tripping over this
   * Unbreak APP_DISABLE_AUTH env parameter
     - Thank you to donmbelembe for finding this and helping to fix it
   * Allow Eloquent models to explicitly define metadata
     - Thank you to NoelDeMartin (noeldemartin@gmail.com) for contributing this
   * Make key-descriptor columns unambiguous when processed on models
   * Flatten residual references to PrimaryKey alias field
   * Handle batch processing requests
   * Annotate EntityGubbins failures with supplied class name
   * Disambiguate order-by columns to stop tripping join queries up
   * Handle HasManyThrough relations where reverse relation is defined

0.3.4 (2017-12-04)
------------------
   * Fix default-value handling for boolean fields
   * Make IronicSerialiser handle empty payloads
   * Retrieve object-map when cached, along with metadata
   * Initialise empty object-map associations as arrays, not null

0.3.3 (2017-11-21)
------------------
   * Cache object map along with metadata
   * Dig object map out of container when building metadata
   * Fix goof that stopped empty result set serialisation in some cases.

0.3.2 (2017-11-15)
------------------
   * Hook Eloquent model $cast array to metadata generation
   * Only load concrete, trait-using models when processing metadata
   * Unconditionally convert default primitive property values to strings

0.3.1 (2017-11-09)
------------------
   * Fix mispointed composer.json dependency for POData
     -  Thank you to kodermax@gmail.com for reporting this
   * Cache table field list lookups
   * Respect hidden/visible getters in metadata processing
   * Require property names to be case-insensitive unique
   * Fix bug that stopped migration attempts
   * Eager-load explicitly-expanded properties during GET queries
   * Memoise repeated calculations to speed up big GET queries
   * Fix bug that omitted PrimaryKey property serialisation
   * Fix orderBy handling, whether via model, relation or query builder

0.3.0 (2017-09-24)
------------------
   * Remix relation calculation and add stronger consistency checks
   * Fix bugs in base type generation
   * Fix bugs in relation calculation
   * Add dry-run config option
   * Implement default and custom bulk-create/update handling
   * Implement relation hookup/disconnect
   * Add config switch to bypass authentication
   * Simulate polymorphic relations a la OData v3
   * Turn Scrutinizer analysis up to 11
   * Implement Laravel-specialised object model serialiser
   * Get skipToken handling working
   * Add capability to use Laravel's passport authentication (if available)
     -  Thank you to Renan William Alves de Paula
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
