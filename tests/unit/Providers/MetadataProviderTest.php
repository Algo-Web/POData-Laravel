<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\ODataMetadata\MetadataManager;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestGetterModel;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
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

        $meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        App::instance('metadata', $meta);

        $classen = [TestModel::class, TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class, TestMonomorphicManySource::class, TestMonomorphicManyTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class,
            TestMorphManyToManyTarget::class, TestMonomorphicOneAndManySource::class,
            TestMonomorphicOneAndManyTarget::class];

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
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($meta, null);
        App::instance(TestModel::class, $testModel);

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        //$meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        $meta = new SimpleMetadataProvider('Data', 'Data');
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
        $this->assertEquals('TestModels', $resources[0]->getName());
    }

    public function testBootHasMigrationsSingleModelWithoutSchema()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

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

    public function testBootHasMigrationsThreeDifferentRelationTypes()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();

        $classen = [TestMonomorphicOneAndManySource::class, TestMonomorphicOneAndManyTarget::class,
            TestMorphManyToManyTarget::class, TestMorphManyToManySource::class, TestMonomorphicSource::class,
            TestMonomorphicTarget::class];

        $types = [];

        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            $testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
            $type = m::mock(ResourceEntityType::class);
            $types[$className] = $type;
        }

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('addResourceSetReferencePropertyBidirectional')
            ->withAnyArgs()->andReturn(null)->atLeast(1);
        $meta->shouldReceive('addResourceReferenceSinglePropertyBidirectional')
            ->withAnyArgs()->andReturn(null)->atLeast(1);
        $meta->shouldReceive('addResourceReferencePropertyBidirectional')
            ->withAnyArgs()->andReturn(null)->atLeast(1);

        App::instance('metadata', $meta);

        $foo->boot();
    }

    public function testOneToManyRelationConsistentBothWays()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();

        $classen = [TestMorphManySource::class, TestMorphTarget::class];

        $types = [];
        $i = 0;
        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            $testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
            $type = m::mock(ResourceType::class);
            $types[$className] = $type;
        }

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('addResourceReferencePropertyBidirectional')
            ->with(m::type(ResourceType::class), m::type(ResourceType::class), 'morphTarget', 'morph')->times(2);
        $meta->shouldReceive('addResourceReferencePropertyBidirectional')
            ->with(m::type(ResourceType::class), m::type(ResourceType::class), 'morph', 'morphTarget')->never();

        App::instance('metadata', $meta);

        $foo->boot();
    }

    public function testAddSingletonDirect()
    {
        $functionName = [get_class($this), 'getterSingleton'];

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($meta, null);
        App::instance(TestModel::class, $testModel);

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();

        $classen = [TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class, TestMonomorphicManySource::class, TestMonomorphicManyTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class,
            TestMorphManyToManyTarget::class, TestMonomorphicOneAndManySource::class,
            TestMonomorphicOneAndManyTarget::class];

        $types = [];
        $i = 0;
        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
            $type = m::mock(ResourceType::class);
            $types[$className] = $type;
        }
        $classen[] = TestModel::class;

        $app = App::make('app');
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->boot();

        $meta = App::make('metadata');
        $types = $meta->getTypes();
        $type = $types[0];

        $meta->createSingleton('single', $type, $functionName);
        $result = $meta->callSingleton('single');
        $this->assertEquals('VNV Nation', $result->name);
    }

    public function testAddSingletonOverFacade()
    {
        // This test, we're verifying that a singleton can be added over one of Laravel's facades - in this case,
        // a dummied-out call to Auth::user() that returns the TestModel set up below
        $functionName = [Auth::class, 'user'];

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($meta, null);
        $testModel->name = 'Commence Primary Ignition';

        App::instance(TestModel::class, $testModel);

        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs(['migrations'])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('has')->withArgs(['metadata'])->andReturn(false)->once();

        $auth = Auth::getFacadeRoot();
        $auth->shouldReceive('user')->andReturn($testModel)->once();

        $classen = [TestModel::class];

        $app = App::make('app');
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->boot();

        $meta = App::make('metadata');
        $types = $meta->getTypes();
        $type = $types[0];

        $meta->createSingleton('single', $type, $functionName);
        $result = $meta->callSingleton('single');
        $this->assertEquals('Commence Primary Ignition', $result->name);
    }

    public static function getterSingleton()
    {
        $model = new TestModel();
        $model->name = "VNV Nation";
        return $model;
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

    public function testPostBootHandlingRoundTrip()
    {
        $foo = new TestProvider($this->app);
        $meta = 'meta';
        $key = 'secret';

        $foo->handlePostBoot(true, false, $key, $meta);
        $this->assertEquals($meta, Cache::get($key));

        $foo->handlePostBoot(false, false, $key, $meta);
        $this->assertEquals(null, Cache::get($key));
    }

    public function testPostBootHandlingHasCacheIsCaching()
    {
        $foo = new TestProvider($this->app);
        $meta = 'meta';
        $key = 'secret';

        $foo->handlePostBoot(true, true, $key, $meta);
        $this->assertEquals(null, Cache::get($key));
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
