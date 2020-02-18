<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/02/20
 * Time: 2:38 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Functional;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\Address;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\City;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\Person;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ExpandTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        putenv('APP_DISABLE_AUTH=true');
    }

    public static function tearDownAfterClass() : void
    {
        putenv('APP_DISABLE_AUTH=false');
    }

    use DatabaseMigrations;

    public function setUp() : void
    {
        parent::setUp();
        $baz = new City();
        $baz->cityId = 'baz';
        $baz->name = 'baz';
        $baz->postcode = 'WTF 0MG';
        $baz->country = 'The Old Dart';
        $this->assertTrue($baz->save());

        $foo = new Address();
        $foo->addressId = 'foo';
        $foo->cityid = 'baz';
        $foo->street = 'street';
        $this->assertTrue($foo->save());

        $bar = new Person();
        $bar->personId = 'bar';
        $bar->addressid = 'foo';
        $bar->name = 'Zoidberg';
        $bar->givenname = 'John';
        $this->assertTrue($bar->save());
    }

    public function testSingleExpandBelongsToFromPeople()
    {
        $url = 'odata.svc/People?$expand=Address';

        $expectedLink = '<link rel="edit" title="Address" href="Addresses(addressId=\'foo\')"/>';
        $expectedId = '<d:addressId m:type="Edm.String">foo</d:addressId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }

    public function testSingleExpandBelongsToFromAddress()
    {
        $url = 'odata.svc/Addresses?$expand=City';

        $expectedLink = '<link rel="edit" title="City" href="Cities(cityId=\'baz\')"/>';
        $expectedId = '<d:cityId m:type="Edm.String">baz</d:cityId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }

    public function testSingleExpandHasManyFromAddress()
    {
        $url = 'odata.svc/Addresses?$expand=Person';

        $expectedLink = '<link rel="edit" title="Person" href="People(personId=\'bar\')"/>';
        $expectedId = '<d:personId m:type="Edm.String">bar</d:personId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }

    public function testSingleExpandHasManyFromCity()
    {
        $url = 'odata.svc/Cities?$expand=Address';

        $expectedLink = '<link rel="edit" title="Address" href="Addresses(addressId=\'foo\')"/>';
        $expectedId = '<d:addressId m:type="Edm.String">foo</d:addressId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }

    public function testSingleRetrieveOffParent()
    {
        $url = 'odata.svc/Addresses(addressId=\'foo\')/City';

        $expectedLink = '<link rel="edit" title="City" href="Cities(cityId=\'baz\')"/>';
        $expectedId = '<d:cityId m:type="Edm.String">baz</d:cityId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }

    public function testDoubleExpandBelongsToThenBelongsTo()
    {
        $url = 'odata.svc/People?$expand=Address/City';

        $expectedLink = '<link rel="edit" title="City" href="Cities(cityId=\'baz\')"/>';
        $expectedId = '<d:personId m:type="Edm.String">bar</d:personId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }

    public function testDoubleExpandHasManyThenHasMany()
    {
        $url = 'odata.svc/Cities?$expand=Address/Person';

        $expectedLink = '<link rel="edit" title="Person" href="People(personId=\'bar\')"/>';
        $expectedId = '<d:personId m:type="Edm.String">bar</d:personId>';
        $result = $this->get($url);
        $this->assertSeeShim($result, $expectedLink);
        $this->assertSeeShim($result, $expectedId);
    }
}
