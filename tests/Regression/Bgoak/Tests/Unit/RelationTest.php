<?php

declare(strict_types=1);
namespace Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Unit\Models;

use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\Address;
use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\City;
use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\Person;
use Tests\Regression\AlgoWeb\PODataLaravel\TestCase;

/**
 * As the name suggests, these tests were added to help diagnose and ultimately fix issue #188, reported by
 * bgoak on 29/01/2020.  #188 reports that multi-level expansion isn't working properly, and since expansion
 * critically depends on the underlying relations working correctly, the initial tests in this class check
 * that all relations defined in the three models reported round-trip.
 *
 * Class BgOakRelationTests
 * @package AlgoWeb\PODataLaravel\Orchestra\Tests\Unit
 */
class RelationTest extends TestCase
{
    public function testCityAddressRelationRoundTrip()
    {
        $foo           = new City();
        $foo->cityId   = 'foo';
        $foo->name     = 'foo';
        $foo->postcode = 'WTF 0MG';
        $foo->country  = 'The Old Dart';
        $this->assertTrue($foo->save());
        $bar            = new Address();
        $bar->addressId = 'bar';
        $bar->cityid    = 'foo';
        $bar->street    = 'street';
        $this->assertTrue($bar->save());

        /** @var Address $nuBar */
        $nuBar = $foo->Address()->firstOrFail();
        $this->assertEquals($bar->getKey(), $nuBar->getKey());
        /** @var City $nuFoo */
        $nuFoo = $nuBar->City()->firstOrFail();
        $this->assertEquals($foo->getKey(), $nuFoo->getKey());
    }

    public function testAddressPersonRelationRoundTrip()
    {
        $baz           = new City();
        $baz->cityId   = 'baz';
        $baz->name     = 'baz';
        $baz->postcode = 'WTF 0MG';
        $baz->country  = 'The Old Dart';
        $this->assertTrue($baz->save());

        $foo            = new Address();
        $foo->addressId = 'foo';
        $foo->cityid    = 'baz';
        $foo->street    = 'street';
        $this->assertTrue($foo->save());

        $bar            = new Person();
        $bar->personId  = 'bar';
        $bar->addressid = 'foo';
        $bar->name      = 'Zoidberg';
        $bar->givenname = 'John';
        $this->assertTrue($bar->save());

        /** @var Person $nuBar */
        $nuBar = $foo->Person()->firstOrFail();
        $this->assertEquals($bar->getKey(), $nuBar->getKey());
        /** @var Address $nuFoo */
        $nuFoo = $nuBar->Address()->firstOrFail();
        $this->assertEquals($foo->getKey(), $nuFoo->getKey());
    }
}
