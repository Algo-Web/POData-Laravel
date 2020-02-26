<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\StringType;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestCastModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestGetterModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicChildOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicOneAndManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicOneAndManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicParentOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceWithUnexposedTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTargetAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTargetChild;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestPolymorphicDualSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Providers\MetadataProviderDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Providers\TestProvider;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

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
    public function setUp() : void
    {
        parent::setUp();
        $map = new Map();
        App::instance('objectmap', $map);
        $holder = new MetadataGubbinsHolder();
        $this->object = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->object->shouldReceive('getRelationHolder')->andReturn($holder);
        self::resetMetadataProvider($this->object);

        $foo = new TestModel();
        self::resetModel($foo);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown() : void
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::boot
     */
    public function testBootNoMigrations()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(false)->once();

        $foo = $this->object;
        $foo->boot();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\MetadataProvider::boot
     */
    public function testBootNoMigrationsExceptionThrown()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->andThrow(new \Exception())->once();

        $foo = $this->object;
        $foo->boot();
    }

    public function testBootHasMigrationsIsCached()
    {
        $this->setUpSchemaFacade();

        $meta = m::mock(SimpleMetadataProvider::class);
        App::instance('metadata', $meta);

        $map = m::mock(Map::class);
        App::instance('objectmap', $map);

        //$cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn('aybabtu')->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn('wombat')->once();
        //Cache::swap($cache);

        $foo = $this->object;
        $foo->shouldReceive('getIsCaching')->andReturn(true)->once();

        $foo->boot();
        $result = App::make('metadata');
        $this->assertEquals('aybabtu', $result);
        $result = App::make('objectmap');
        $this->assertEquals('wombat', $result);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testBootHasMigrationsShouldBeCached()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $this->setUpSchemaFacade();

        $meta = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $meta);

        $classen = [TestModel::class, TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class, TestMonomorphicManySource::class, TestMonomorphicManyTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class,
            TestMorphManyToManyTarget::class, TestMonomorphicOneAndManySource::class, TestMorphTargetAlternate::class,
            TestMonomorphicOneAndManyTarget::class, TestCastModel::class, TestMorphOneSourceAlternate::class,
            TestMorphManySourceAlternate::class, TestMorphManySourceWithUnexposedTarget::class,
            TestPolymorphicDualSource::class, TestMorphTargetChild::class, TestMonomorphicChildOfMorphTarget::class,
            TestMonomorphicParentOfMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $this->setUpSchemaFacade();

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->once();
        $cache->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('objectmap', m::any(), 10)->once();
        Cache::swap($cache);

        $foo = $this->object;
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

        $this->setUpSchemaFacade();

        $cacheStore = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cacheStore->shouldReceive('forget')->withArgs(['metadata'])->andReturnNull()->once();
        $cacheStore->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cacheStore->shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::swap($cacheStore);

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();
        $meta = App::make('metadata');

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(1, count($resources));
        $this->assertTrue($resources[0] instanceof ResourceSet);
        $this->assertEquals('TestModels', $resources[0]->getName());
    }

    public function testBootHasMigrationsSingleModelWithoutSchema()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metaRaw);
        App::instance(TestModel::class, $testModel);

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();
        $meta = App::make('metadata');

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(1, count($resources));
    }

    public function testBootHasMigrationsThreeDifferentRelationTypes()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $classen = [TestMonomorphicOneAndManySource::class, TestMonomorphicOneAndManyTarget::class,
            TestMorphManyToManyTarget::class, TestMorphManyToManySource::class, TestMonomorphicSource::class,
            TestMonomorphicTarget::class];

        $types = [];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
            $type = m::mock(ResourceEntityType::class);
            $type->shouldReceive('getCustomState')->andReturn(m::mock(ResourceSet::class));
            $type->shouldReceive('resolveProperty')->andReturn(null);
            $type->shouldReceive('getName')->andReturn($className);
            $types[$className] = $type;
        }

        $placeholder = m::mock(ResourceSet::class);

        $morphTarget = m::mock(ResourceSet::class);

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->never();

        $abstract = $this->createAbstractMockType();

        $meta = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $meta);

        $foo->boot();
    }

    public function testOneToManyRelationConsistentBothWays()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $classen = [TestMorphManySource::class, TestMorphTarget::class];

        $types = [];
        $i = 0;
        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
            $type = m::mock(ResourceEntityType::class);
            $type->shouldReceive('getCustomState')->andReturn(m::mock(ResourceSet::class));
            $type->shouldReceive('resolveProperty')->andReturn(null);
            $type->shouldReceive('getName')->andReturn($className);
            $type->shouldReceive('setMediaLinkEntry')->passthru();
            $types[$className] = $type;
        }

        $abstract = $this->createAbstractMockType();
        $placeholder = $abstract->getCustomState();

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->never();

        $meta = new SimpleMetadataProvider('data', 'data');
        App::instance('metadata', $meta);

        $foo->boot();
    }

    public function testAddSingletonDirect()
    {
        $functionName = [get_class($this), 'getterSingleton'];

        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metaRaw, null);
        App::instance(TestModel::class, $testModel);

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $classen = [TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class, TestMonomorphicManySource::class, TestMonomorphicManyTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class,
            TestMorphManyToManyTarget::class, TestMonomorphicOneAndManySource::class,
            TestMonomorphicOneAndManyTarget::class, TestMonomorphicChildOfMorphTarget::class,
            TestMonomorphicParentOfMorphTarget::class];

        $types = [];
        $i = 0;
        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
            $type = m::mock(ResourceType::class);
            $types[$className] = $type;
        }
        $classen[] = TestModel::class;

        $app = App::make('app');
        $foo = $this->object;
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

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $auth = Auth::getFacadeRoot();
        Auth::shouldReceive('user')->andReturn($testModel)->once();

        $classen = [TestModel::class];

        $app = App::make('app');
        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->boot();

        $meta = App::make('metadata');
        $types = $meta->getTypes();
        $type = $types[0];

        $meta->createSingleton('single', $type, $functionName);
        $result = $meta->callSingleton('single');
        $this->assertEquals('Commence Primary Ignition', $result->name);
    }

    public function testGetDefaultNamespace()
    {
        App::shouldReceive('getNamespace')->andThrow(new \Exception())->once();

        $foo = m::mock(MetadataProviderDummy::class)->makePartial();

        $expected = 'App';
        $actual = $foo->getAppNamespace();
        $this->assertEquals($expected, $actual);
    }

    public function testGetNonDefaultNamespace()
    {
        App::shouldReceive('getNamespace')->andReturn('hammertime')->once();

        $foo = m::mock(MetadataProviderDummy::class)->makePartial();

        $expected = 'hammertime';
        $actual = $foo->getAppNamespace();
        $this->assertEquals($expected, $actual);
    }

    public static function getterSingleton()
    {
        $model = new TestModel();
        $model->name = 'VNV Nation';
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

        $result = App::make('objectmap');
        $this->assertTrue($result instanceof Map);
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

    private function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);
    }

    /**
     * @return m\MockInterface
     */
    private function createAbstractMockType()
    {
        $abstractSet = m::mock(ResourceSet::class);

        $iType = new StringType();

        $abstract = m::mock(ResourceEntityType::class);
        $abstract->shouldReceive('isAbstract')->andReturn(true);
        $abstract->shouldReceive('getFullName')->andReturn('polyMorphicPlaceholder');
        $abstract->shouldReceive('getName')->andReturn('polyMorphicPlaceholder');
        $abstract->shouldReceive('setCustomState')->andReturnNull();
        $abstract->shouldReceive('getCustomState')->andReturn($abstractSet);
        $abstract->shouldReceive('getInstanceType')->andReturn($iType);
        $abstract->shouldReceive('addProperty')->andReturn(null);
        $abstract->shouldReceive('keyProperty')->andReturn(null);
        return $abstract;
    }
}
