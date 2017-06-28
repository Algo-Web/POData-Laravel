[![Build Status](https://travis-ci.org/Algo-Web/POData-Laravel.svg?branch=master)](https://travis-ci.org/Algo-Web/POData-Laravel)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Algo-Web/POData-Laravel/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Algo-Web/POData-Laravel/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/Algo-Web/POData-Laravel/badge.svg?branch=master)](https://coveralls.io/github/Algo-Web/POData-Laravel?branch=master)
[![Latest Stable Version](https://poser.pugx.org/algo-web/podata-laravel/v/stable)](https://packagist.org/packages/algo-web/podata-laravel)
[![Latest Unstable Version](https://poser.pugx.org/algo-web/podata-laravel/v/unstable)](https://packagist.org/packages/algo-web/podata-laravel)
[![Total Downloads](https://poser.pugx.org/algo-web/podata-laravel/downloads)](https://packagist.org/packages/algo-web/podata-laravel)
[![Monthly Downloads](https://poser.pugx.org/algo-web/podata-laravel/d/monthly)](https://packagist.org/packages/algo-web/podata-laravel)
[![Daily Downloads](https://poser.pugx.org/algo-web/podata-laravel/d/daily)](https://packagist.org/packages/algo-web/podata-laravel)

# POData-Laravel
Composer Package to provide Odata functionality to Laravel
to install run
```
composer require algo-web/podata-laravel
```

Edit `config/app.php` and add this to providers section:

```php
AlgoWeb\PODataLaravel\Providers\MetadataProvider::class,
AlgoWeb\PODataLaravel\Providers\MetadataRouteProvider::class,
AlgoWeb\PODataLaravel\Providers\QueryProvider::class,
```

You then add the trait to the models you want to expose.

```php
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
```

-- Known Limitations --

* Cannot expose two models with the same class name in different
namespaces - trying to expose both App\Foo\Model and App\Bar\Model
will trip an exception complaining that resource set has already been
added.
* This can be worked around by setting a custom endpoint name on one
of the colliding models.

-- Configuration options --
* APP_METADATA_CACHING - Whether or not to turn model metadata caching on
* APP_METADATA_CACHE_DURATION - If caching, how long (in minutes) to
retain cached metadata


## Features Supported

(thanks to @renanwilliam for the initial version of this list)

* [ ] Full CRUD Support
* [ ] $count
* [ ] $filter
  * [ ] Comparison Operators
    * [ ] eq
    * [ ] ne
    * [ ] lt
    * [ ] le
    * [ ] gt
    * [ ] ge
  * [ ] Logical Operators
    * [ ] and
    * [ ] or
    * [ ] not
  * [ ] Comparison Operators
    * [ ] has
  * [ ] String Functions
    * [ ] indexof
    * [ ] contains
    * [ ] endswith
    * [ ] startswith
    * [ ] length
    * [ ] substring
    * [ ] tolower
    * [ ] toupper
    * [ ] trim
    * [ ] concat
  * [ ] Arithmetic Operators
    * [ ] add
    * [ ] sub
    * [ ] mul
    * [ ] div
    * [ ] mod
  * [ ] Date Functions
    * [ ] year
    * [ ] month
    * [ ] day
    * [ ] hour
    * [ ] minute
    * [ ] second
    * [ ] fractionalseconds
    * [ ] date
    * [ ] time
    * [ ] totaloffsetminutes
    * [ ] now
    * [ ] mindatetime
    * [ ] maxdatetime
  * [ ] Math Functions
    * [ ] round
    * [ ] floor
    * [ ] ceiling
* [x] $select
* [x] $top
* [x] $skip
* [x] $orderby
* [x] $expand
* [ ] $metadata generation (Nonstandard)