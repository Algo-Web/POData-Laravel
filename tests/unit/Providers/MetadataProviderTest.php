<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestGetterModel;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Mockery as m;

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
     */
    public function testBootNoMigrations()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(false)->once();

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->boot();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::boot
     */
    public function testBootNoMigrationsExceptionThrown()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->andThrow(new \Exception())->once();

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->boot();
    }

    public function testBootHasMigrationsIsCached()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        App::instance('metadata', $meta);

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('has')->withArgs(['metadata'])->andReturn(true)->once();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn('aybabtu')->once();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(true)->once();

        $foo->boot();
        $result = App::make('metadata');
        $this->assertEquals('aybabtu', $result);
    }

    public function testBootHasMigrationsShouldBeCached()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        App::instance('metadata', $meta);

        $classen = [TestModel::class, TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            $testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn('aybabtu')->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->once();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(true)->once();

        $foo->boot();
    }

    public function testBootHasMigrationsSingleModel()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true];

        $testModel = new TestModel($meta, null);
        App::instance(TestModel::class, $testModel);

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        App::instance('metadata', $meta);

        $cacheStore = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cacheStore->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();
        $cacheStore->shouldReceive('forget')->withArgs(['metadata'])->andReturnNull()->once();
        Cache::swap($cacheStore);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(1, count($resources));
        $this->assertTrue($resources[0] instanceof ResourceSet);
        $this->assertEquals('testmodel', $resources[0]->getName());
    }

    public function testBootHasMigrationsSingleModelWithoutSchema()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true];

        $testModel = m::mock(TestModel::class)->makePartial();
        $testModel->shouldReceive('getXmlSchema')->andReturn(null);
        App::instance(TestModel::class, $testModel);

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        App::instance('metadata', $meta);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(0, count($resources));
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::register
     */
    public function testRegister()
    {
        $foo = new MetadataProvider($this->app);
        $foo->register();

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
