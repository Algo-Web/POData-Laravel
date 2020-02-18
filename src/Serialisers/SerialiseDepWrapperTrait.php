<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/02/20
 * Time: 6:00 PM.
 */
namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\IService;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

trait SerialiseDepWrapperTrait
{
    /**
     * The service implementation.
     *
     * @var IService
     */
    protected $service;

    /**
     * Holds reference to segment stack being processed.
     *
     * @var SegmentStack
     */
    protected $stack;

    /**
     * Lightweight stack tracking for recursive descent fill.
     * @var array
     */
    protected $lightStack = [];

    /**
     * Request description instance describes OData request the
     * the client has submitted and result of the request.
     *
     * @var RequestDescription
     */
    protected $request;


    /**
     * @var ModelSerialiser
     */
    protected $modelSerialiser;

    /**
     * @var IMetadataProvider
     */
    protected $metaProvider;

    /**
     * Absolute service Uri.
     *
     * @var string
     */
    protected $absoluteServiceUri;

    /**
     * Absolute service Uri with slash.
     *
     * @var string
     */
    protected $absoluteServiceUriWithSlash;

    /**
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Sets the data service instance.
     *
     * @param  IService $service
     * @return void
     */
    public function setService(IService $service)
    {
        $this->service = $service;
        $this->absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
    }

    /**
     * Gets the segment stack instance.
     *
     * @return SegmentStack
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @throws InvalidOperationException
     * @return RequestDescription
     */
    public function getRequest()
    {
        if (null == $this->request) {
            throw new InvalidOperationException('Request not yet set');
        }

        return $this->request;
    }

    /**
     * Sets reference to the request submitted by client.
     *
     * @param RequestDescription $request
     */
    public function setRequest(RequestDescription $request)
    {
        $this->request = $request;
        $this->stack->setRequest($request);
    }

    /**
     * @return ModelSerialiser
     */
    public function getModelSerialiser()
    {
        return $this->modelSerialiser;
    }

    /**
     * @return IMetadataProvider
     */
    protected function getMetadata()
    {
        if (null == $this->metaProvider) {
            $this->metaProvider = App::make('metadata');
        }
        return $this->metaProvider;
    }

    /**
     * @return array
     */
    protected function getLightStack()
    {
        return $this->lightStack;
    }

    /**
     * @throws InvalidOperationException
     */
    protected function loadStackIfEmpty()
    {
        if (0 == count($this->lightStack)) {
            $typeName = $this->getRequest()->getTargetResourceType()->getName();
            array_push($this->lightStack, ['type' => $typeName, 'property' => $typeName, 'count' => 1]);
        }
    }

    /**
     * Resource set wrapper for the resource being serialized.
     *
     * @throws InvalidOperationException
     * @return ResourceSetWrapper
     */
    protected function getCurrentResourceSetWrapper()
    {
        $segmentWrappers = $this->getStack()->getSegmentWrappers();
        $count = count($segmentWrappers);

        return 0 == $count ? $this->getRequest()->getTargetResourceSetWrapper() : $segmentWrappers[$count-1];
    }

    /**
     * @param int $newCount
     */
    protected function updateLightStack($newCount)
    {
        $this->lightStack[$newCount - 1]['count']--;
        if (0 == $this->lightStack[$newCount - 1]['count']) {
            array_pop($this->lightStack);
        }
    }
}
