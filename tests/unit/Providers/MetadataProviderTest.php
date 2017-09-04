<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\ODataMetadata\MetadataManager;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestCastModel;
use AlgoWeb\PODataLaravel\Models\TestGetterModel;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetAlternate;
use AlgoWeb\PODataLaravel\Models\TestPolymorphicDualSource;
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
use POData\Providers\Metadata\Type\StringType;

/**
 * Generated Test Class.
 */
class MetadataProviderTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Providers\MetadataProviderOld
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->object->reset();
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

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        App::instance('metadata', $meta);

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn('aybabtu')->once();
        Cache::swap($cache);

        $foo = $this->object;
        $foo->shouldReceive('getIsCaching')->andReturn(true)->once();

        $foo->boot();
        $result = App::make('metadata');
        $this->assertEquals('aybabtu', $result);
    }

    public function testBootHasMigrationsShouldBeCached()
    {
        $this->setUpSchemaFacade();

        $abstract = $this->createAbstractMockType();

        $meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('addEntityType')->andReturn($abstract)->once();
        $meta->shouldReceive('addResourceSet')->andReturnNull()->once();
        App::instance('metadata', $meta);

        $classen = [TestModel::class, TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class, TestMonomorphicManySource::class, TestMonomorphicManyTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class,
            TestMorphManyToManyTarget::class, TestMonomorphicOneAndManySource::class, TestMorphTargetAlternate::class,
            TestMonomorphicOneAndManyTarget::class, TestCastModel::class, TestMorphOneSourceAlternate::class,
            TestMorphManySourceAlternate::class, TestMorphManySourceWithUnexposedTarget::class,
            TestPolymorphicDualSource::class];

        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            $testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $this->setUpSchemaFacade();

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->once();
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

        //$meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        $meta = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $meta);

        $cacheStore = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cacheStore->shouldReceive('forget')->withArgs(['metadata'])->andReturnNull()->once();
        Cache::swap($cacheStore);

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(2, count($resources));
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

        $this->setUpSchemaFacade();

        $abstract = $this->createAbstractMockType();

        $meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('addEntityType')->andReturn($abstract)->once();
        App::instance('metadata', $meta);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(1, count($resources));
    }

    public function testBootHasMigrationsThreeDifferentRelationTypes()
    {
        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

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
            $type->shouldReceive('getCustomState')->andReturn(m::mock(ResourceSet::class));
            $type->shouldReceive('resolveProperty')->andReturn(null);
            $types[$className] = $type;
        }

        $placeholder = m::mock(ResourceSet::class);

        $morphTarget = m::mock(ResourceSet::class);

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

        $abstract = $this->createAbstractMockType();

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('addKeyProperty')->andReturnNull()->once();
        $meta->shouldReceive('addResourceSet')->andReturnNull()->once();
        $meta->shouldReceive('resolveResourceSet')
            ->withArgs(['polyMorphicPlaceholders'])->andReturn($placeholder);
        $meta->shouldReceive('resolveResourceSet')
            ->withArgs([TestMorphManyToManyTarget::class])->andReturn($morphTarget);
        $meta->shouldReceive('addEntityType')->andReturn($abstract)->atLeast(1);
        $meta->shouldReceive('addResourceSetReferenceProperty')
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
        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestMorphManySource::class, TestMorphTarget::class];

        $types = [];
        $i = 0;
        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            $testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
            $type = m::mock(ResourceEntityType::class);
            $type->shouldReceive('getCustomState')->andReturn(m::mock(ResourceSet::class));
            $type->shouldReceive('resolveProperty')->andReturn(null);
            $types[$className] = $type;
        }

        $abstract = $this->createAbstractMockType();
        $placeholder = $abstract->getCustomState();

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('addKeyProperty')->andReturnNull()->once();
        $meta->shouldReceive('resolveResourceSet')
            ->withArgs(['polyMorphicPlaceholders'])->andReturn($placeholder);
        $meta->shouldReceive('addResourceSet')->andReturnNull()->once();
        $meta->shouldReceive('addEntityType')->andReturn($abstract)->atLeast(1);
        $meta->shouldReceive('addResourceReferenceProperty')
            ->with(m::type(ResourceEntityType::class), 'morphTarget', m::any())
            ->atLeast(1);
        $meta->shouldReceive('addResourceSetReferenceProperty')
            ->with(m::type(ResourceEntityType::class), 'morphTarget', m::any())
            ->atLeast(1);
        $meta->shouldReceive('addResourceSetReferenceProperty')
            ->with(m::type(ResourceEntityType::class), 'morph', m::any())
            ->atLeast(1);

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

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

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
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $auth = Auth::getFacadeRoot();
        $auth->shouldReceive('user')->andReturn($testModel)->once();

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
        $foo = new MetadataProviderOld($this->app);
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
