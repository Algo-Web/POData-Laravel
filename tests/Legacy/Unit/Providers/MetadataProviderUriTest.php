<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\EntitySetRights;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use Symfony\Component\HttpFoundation\HeaderBag;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\MetadataProviderDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class MetadataProviderUriTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $map = new Map();
        App::instance('objectmap', $map);
        $foo = m::mock(MetadataProvider::class)->makePartial();
        self::resetMetadataProvider($foo);
    }

    public function tearDown(): void
    {
        MetadataProvider::setAfterExtract(null);
        MetadataProvider::setAfterUnify(null);
        MetadataProvider::setAfterVerify(null);
        MetadataProvider::setAfterImplement(null);

        parent::tearDown();
    }

    public function testUriOfMonomorphicOneToOneRelation()
    {
        $metaRaw                = [];
        $metaRaw['id']          = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']        = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']       = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['many_id']     = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['one_source']  = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['one_id']      = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $reqUrlString = 'http://localhost/odata.svc/TestMonomorphicSources(id=1)/oneSource';

        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url($reqUrlString);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicSources(id=1)/oneSource');
        $request->shouldReceive('fullUrl')
            ->andReturn($reqUrlString);
        $request->initialize();

        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        MetadataProvider::setAfterExtract(function (Map $objectMap) {
            $assoc = $objectMap->getAssociations();
            $this->assertEquals(0, count($assoc));
            $entities = $objectMap->getEntities();
            $this->assertEquals(2, count($entities));
        });

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $meta = App::make('metadata');

        $context = new IlluminateOperationContext($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::ALL);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(400);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $query = m::mock(IQueryProvider::class);

        $wrapper = new ProvidersWrapper($meta, $query, $config);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);

        $desc = ResourcePathProcessor::process($service);
        $this->assertTrue($desc->getSegments()[0]->isSingleResult());
        // we're collecting one end of 1:1 relation, without specific qualifiers, so it has to be a single result
        $this->assertTrue($desc->getSegments()[1]->isSingleResult());
    }

    public function testUriOfMonomorphicOneToManyRelation()
    {
        $metaRaw                = [];
        $metaRaw['id']          = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']        = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']       = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['many_id']     = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['one_source']  = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['one_id']      = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $reqUrlString = 'http://localhost/odata.svc/TestMonomorphicSources(id=1)/manySource';

        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url($reqUrlString);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicSources(id=1)/manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn($reqUrlString);
        $request->initialize();

        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        MetadataProvider::setAfterUnify(function (Map $objectMap) {
            $assoc = $objectMap->getAssociations();
            $this->assertEquals(2, count($assoc));
            $entities = $objectMap->getEntities();
            $this->assertEquals(2, count($entities));
        });

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $meta = App::make('metadata');

        $context = new IlluminateOperationContext($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::ALL);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(400);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $query = m::mock(IQueryProvider::class);

        $wrapper = new ProvidersWrapper($meta, $query, $config);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);

        $desc = ResourcePathProcessor::process($service);
        $this->assertTrue($desc->getSegments()[0]->isSingleResult());
        // we're collecting many end of 1:N relation, without specific qualifiers, so it has to be a multiple result
        $this->assertFalse($desc->getSegments()[1]->isSingleResult());
    }

    public function testUriOfMonomorphicManyToManyRelation()
    {
        $metaRaw          = [];
        $metaRaw['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $reqUrlString = 'http://localhost/odata.svc/TestMonomorphicManySources(id=1)/manySource';

        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url($reqUrlString);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/TestMonomorphicManySources(id=1)/manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn($reqUrlString);
        $request->initialize();

        $classen = [TestMonomorphicManySource::class, TestMonomorphicManyTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $meta = App::make('metadata');

        $context = new IlluminateOperationContext($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::ALL);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(400);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $query = m::mock(IQueryProvider::class);

        $wrapper = new ProvidersWrapper($meta, $query, $config);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);

        $desc = ResourcePathProcessor::process($service);
        $this->assertTrue($desc->getSegments()[0]->isSingleResult());
        // we're collecting many end of 1:N relation, without specific qualifiers, so it has to be a multiple result
        $this->assertFalse($desc->getSegments()[1]->isSingleResult());
    }

    public function testUriOfPolymorphicOneToOneRelation()
    {
        $metaRaw             = [];
        $metaRaw['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']    = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $reqUrlString = 'http://localhost/odata.svc/TestMorphOneSources(id=1)/morphTarget';

        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url($reqUrlString);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/TestMorphOneSources(id=1)/manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn($reqUrlString);
        $request->initialize();

        $classen = [TestMorphOneSource::class, TestMorphTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $meta = App::make('metadata');

        $context = new IlluminateOperationContext($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::ALL);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(400);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $query = m::mock(IQueryProvider::class);

        $wrapper = new ProvidersWrapper($meta, $query, $config);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);

        $desc = ResourcePathProcessor::process($service);
        $this->assertTrue($desc->getSegments()[0]->isSingleResult());
        // we're collecting one end of 1:1 relation, without specific qualifiers, so it has to be a single result
        $this->assertTrue($desc->getSegments()[1]->isSingleResult());
    }

    public function testUriOfPolymorphicOneToManyRelation()
    {
        $metaRaw                    = [];
        $metaRaw['id']              = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']            = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']           = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $reqUrlString = 'http://localhost/odata.svc/TestMorphManySourceAlternates(alternate_id=1)/morphTarget';

        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url($reqUrlString);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/TestMorphManySourceAlternates(id=1)/manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn($reqUrlString);
        $request->initialize();

        $classen = [TestMorphManySourceAlternate::class, TestMorphTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }
        MetadataProvider::setAfterVerify(function (Map $objectMap) use ($classen) {
            $entities = $objectMap->getEntities();
            $this->assertEquals(count($classen), count($entities), 'The object map contained to many entities');
            $this->assertArrayHasKey(\Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate::class, $entities);
            $this->assertArrayHasKey(\Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget::class, $entities);
            $morphTarget = $entities[\Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget::class];
            $MorphManySourceAlternate = $entities[\Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate::class];
            $morphTargetStubs = $morphTarget->getStubs();
            $this->assertEquals(5, count($morphTargetStubs));
            $MorphManySourceAlternateStubs = $MorphManySourceAlternate->getStubs();
            $this->assertEquals(1, count($MorphManySourceAlternateStubs));
            $morphTargetAssoc = $morphTarget->getAssociations();
            $this->assertEquals(1, count($morphTargetAssoc));
            foreach ($morphTargetAssoc as $key => $assoc) {
                $this->assertTrue($assoc instanceof AssociationMonomorphic);
                $assocTypes = $assoc->getAssociationType();
                $this->assertEquals(AssociationType::ONE_TO_MANY, $assocTypes->getValue());
            }
            $morphManyAssoc = $MorphManySourceAlternate->getAssociations();
            $this->assertEquals(1, count($morphManyAssoc));
            foreach ($morphManyAssoc as $key => $assoc) {
                $this->assertTrue($assoc instanceof AssociationMonomorphic);
                $assocTypes = $assoc->getAssociationType();
                $this->assertEquals(AssociationType::ONE_TO_MANY, $assocTypes->getValue());
            }
        });
        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $meta = App::make('metadata');

        $context = new IlluminateOperationContext($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::ALL);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(400);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $query = m::mock(IQueryProvider::class);

        $wrapper = new ProvidersWrapper($meta, $query, $config);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);

        $desc = ResourcePathProcessor::process($service);
        $this->assertTrue($desc->getSegments()[0]->isSingleResult());
        // we're collecting many end of 1:N relation, without specific qualifiers, so it has to be a multiple result
        $this->assertFalse($desc->getSegments()[1]->isSingleResult());
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \POData\Common\UrlFormatException
     * @throws \ReflectionException
     */
    public function testUriOfPolymorphicManyToManyRelation()
    {
        $metaRaw          = [];
        $metaRaw['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $reqUrlString = 'http://localhost/odata.svc/TestMorphManyToManySources(id=1)/manySource';

        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url($reqUrlString);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/TestMorphManyToManySources(id=1)/manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn($reqUrlString);
        $request->initialize();

        $classen = [TestMorphManyToManySource::class, TestMorphManyToManyTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        MetadataProvider::setAfterImplement(function (Map $objectMap) {
            $assoc = $objectMap->getAssociations();
            $this->assertEquals(1, count($assoc));
            $entities = $objectMap->getEntities();
            $this->assertEquals(2, count($entities));
        });

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $meta = App::make('metadata');

        $context = new IlluminateOperationContext($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getEntitySetAccessRule')->andReturn(EntitySetRights::ALL);
        $config->shouldReceive('getEntitySetPageSize')->andReturn(400);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $query = m::mock(IQueryProvider::class);

        $wrapper = new ProvidersWrapper($meta, $query, $config);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);

        $desc = ResourcePathProcessor::process($service);
        $this->assertTrue($desc->getSegments()[0]->isSingleResult());
        // we're collecting many end of 1:N relation, without specific qualifiers, so it has to be a multiple result
        $this->assertFalse($desc->getSegments()[1]->isSingleResult());
    }


    private function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);
    }

    /**
     * @return m\Mock
     */
    protected function setUpRequest()
    {
        $this->setUpSchemaFacade();
        $request = m::mock(Request::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request->initialize();
        $request->headers = new HeaderBag(['CONTENT_TYPE' => 'application/atom+xml']);
        $request->setMethod('GET');
        $request->shouldReceive('getBaseUrl')->andReturn('http://localhost/');
        $request->shouldReceive('getQueryString')->andReturn('');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('isSecure')->andReturn(false);
        $request->shouldReceive('getPort')->andReturn(80);
        return $request;
    }

    /**
     * @param $reqUrl
     * @param $baseUrl
     * @param  mixed           $requestVer
     * @param  mixed           $maxVer
     * @return m\MockInterface
     */
    private function setUpMockHost($reqUrl, $baseUrl, $requestVer = '1.0', $maxVer = '3.0')
    {
        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn($requestVer);
        $host->shouldReceive('getRequestMaxVersion')->andReturn($maxVer);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        return $host;
    }
}
