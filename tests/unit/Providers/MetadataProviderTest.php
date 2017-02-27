<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use POData\Providers\Metadata\SimpleMetadataProvider;

/**
 * Generated Test Class.
 */
class MetadataProviderTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Providers\MetadataProvider
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
//        $this->object = new \AlgoWeb\PODataLaravel\Providers\MetadataProvider();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::boot
     * @todo   Implement testBoot().
     */
    public function testBootNoMigrations()
    {
        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class)->makePartial();
        $foo = new \AlgoWeb\PODataLaravel\Providers\MetadataProvider($app);
        $result = $foo->boot();
    }

    public function testBootHasMigrationsIsNotCached()
    {
        config(['APP_METADATA_CACHING' => true]);
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class);

        $cache = Cache::getFacadeRoot();
        $cache->shouldReceive('has')->withArgs(['metadata'])->andReturn(true);
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn('aybabtu');

        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class)->makePartial();
        $app->shouldReceive('make')->withArgs(['metadata'])->andReturn($meta)->once();
        $foo = new \AlgoWeb\PODataLaravel\Providers\MetadataProvider($app);

        $expected = 'Method Mockery_2_Illuminate_Database_Connection::getDoctrineDriver() does not'
                    .' exist on this mock object';
        $actual = null;

        try {
            $foo->boot();
        } catch (\BadMethodCallException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::register
     * @todo   Implement testRegister().
     */
    public function testRegister()
    {
        $foo = new MetadataProvider($this->app);

        $result = App::make('metadata');
        $this->assertTrue($result instanceof SimpleMetadataProvider);
        $this->assertEquals('Data', $result->getContainerName());
        $this->assertEquals('Data', $result->getContainerNameSpace());
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::pathsToPublish
     * @todo   Implement testPathsToPublish().
     */
    public function testPathsToPublish()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::commands
     * @todo   Implement testCommands().
     */
    public function testCommands()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::provides
     * @todo   Implement testProvides().
     */
    public function testProvides()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::when
     * @todo   Implement testWhen().
     */
    public function testWhen()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::isDeferred
     * @todo   Implement testIsDeferred().
     */
    public function testIsDeferred()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::compiles
     * @todo   Implement testCompiles().
     */
    public function testCompiles()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::__call
     * @todo   Implement test__call().
     */
    public function test__call()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
