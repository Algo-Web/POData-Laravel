<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\ODataMetadata\MetadataManager;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataProviderDummy;
use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestCastModel;
use AlgoWeb\PODataLaravel\Models\TestGetterModel;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicChildOfMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicParentOfMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphManySourceWithUnexposedTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetChild;
use AlgoWeb\PODataLaravel\Models\TestPolymorphicDualSource;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\StringType;

/**
 * Generated Test Class.
 */
class MetadataProviderNewTest extends TestCase
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
        $holder = new MetadataGubbinsHolder();
        $this->object = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->object->shouldReceive('getRelationHolder')->andReturn($holder);
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

    public function testConstruct()
    {
        $app = m::mock(\Illuminate\Contracts\Foundation\Application::class);
        $foo = new MetadataProvider($app);
        $this->assertTrue($foo->getRelationHolder() instanceof MetadataGubbinsHolder);
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
        Cache::swap($cache);

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
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
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metaRaw);
        App::instance(TestModel::class, $testModel);

        $this->setUpSchemaFacade();

        $meta = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $meta);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $foo = $this->object;
        $foo->shouldReceive('getCandidateModels')->andReturn([TestModel::class]);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();

        $foo->boot();

        $resources = $meta->getResourceSets();
        $this->assertTrue(is_array($resources));
        $this->assertEquals(2, count($resources));
    }

    public function testBootHasMigrationsThreeDifferentRelationTypes()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

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
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

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
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

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
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestGetterModel::class, TestMorphManySource::class, TestMorphOneSource::class,
            TestMorphTarget::class, TestMonomorphicManySource::class, TestMonomorphicManyTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class,
            TestMorphManyToManyTarget::class, TestMonomorphicOneAndManySource::class,
            TestMonomorphicOneAndManyTarget::class];
        shuffle($classen);

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

    public function testMonomorphicChildOfKnownSidePolymorphicModel()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $expected = [];
        $expected[] = [
            'principalType' => TestMorphTarget::class,
            'principalRSet' => 'polyMorphicPlaceholder',
            'principalMult' => '*',
            'principalProp' => 'monomorphicChildren',
            'dependentType' => TestMonomorphicChildOfMorphTarget::class,
            'dependentRSet' => TestMonomorphicChildOfMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'morphTarget'
        ];

        $classen = [TestMonomorphicChildOfMorphTarget::class, TestMorphTarget::class];
        //shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw, null);
            App::instance($className, $testModel);
        }

        $foo = new MetadataProviderDummy(App::make('app'));
        $foo->setCandidateModels($classen);

        $actual = $foo->getRepairedRoundTripRelations();

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testMonomorphicParentOfKnownSidePolymorphicModel()
    {
        $expected = [];
        $expected[] = [
            'principalType' => TestMorphTarget::class,
            'principalRSet' => 'polyMorphicPlaceholder',
            'principalMult' => '1',
            'principalProp' => 'monomorphicParent',
            'dependentType' => TestMonomorphicParentOfMorphTarget::class,
            'dependentRSet' => TestMonomorphicParentOfMorphTarget::class,
            'dependentMult' => '*',
            'dependentProp' => 'morphTargets'
        ];

        $holder = new MetadataGubbinsHolder();

        $classen = [TestMonomorphicParentOfMorphTarget::class, TestMorphTarget::class];
        shuffle($classen);

        $types = [];

        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            $this->assertTrue($testModel instanceof Model, get_class($testModel));
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
            $type = m::mock(ResourceType::class);
            $types[$className] = $type;
        }

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);
        $foo->reset();

        $actual = $foo->getRepairedRoundTripRelations();

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
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
