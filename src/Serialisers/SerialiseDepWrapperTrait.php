<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/02/20
 * Time: 6:00 PM
 */

namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\IService;
use POData\Providers\Metadata\IMetadataProvider;
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
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService()
    {
        return $this->service;
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
     * @return RequestDescription
     * @throws InvalidOperationException
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
}
