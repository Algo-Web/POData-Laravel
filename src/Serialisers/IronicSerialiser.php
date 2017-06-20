<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

class IronicSerialiser implements IObjectSerialiser
{
    /**
     * The service implementation.
     *
     * @var IService
     */
    protected $service;

    /**
     * Request description instance describes OData request the
     * the client has submitted and result of the request.
     *
     * @var RequestDescription
     */
    protected $request;

    /**
     * Collection of complex type instances used for cycle detection.
     *
     * @var array
     */
    protected $complexTypeInstanceCollection;

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
     * Holds reference to segment stack being processed.
     *
     * @var SegmentStack
     */
    protected $stack;

    /**
     * @param IService           $service Reference to the data service instance
     * @param RequestDescription $request Type instance describing the client submitted request
     */
    public function __construct(IService $service, RequestDescription $request = null)
    {
        $this->service = $service;
        $this->request = $request;
        $this->absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->stack = new SegmentStack($request);
        $this->complexTypeInstanceCollection = [];
    }

    /**
     * Write a top level entry resource.
     *
     * @param mixed $entryObject Reference to the entry object to be written
     *
     * @return ODataEntry
     */
    public function writeTopLevelElement($entryObject)
    {
        $requestTargetSource = $this->getRequest()->getTargetSource();
        $resourceType = $this->getRequest()->getTargetResourceType();
        $rawProp = $resourceType->getAllProperties();
        $relProp = [];
        foreach ($rawProp as $prop) {
            if ($prop->getResourceType() instanceof ResourceEntityType) {
                $relProp[] = $prop;
            }
        }

        $resourceSet = $resourceType->getCustomState();
        assert($resourceSet instanceof ResourceSet);
        $title = $resourceType->getName();
        $type = $resourceType->getFullName();

        $relativeUri = $this->getEntryInstanceKey(
            $entryObject,
            $resourceType,
            $resourceSet->getName()
        );
        $absoluteUri = rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri;

        list($mediaLink, $mediaLinks) = $this->writeMediaData($entryObject, $type, $relativeUri, $resourceType);

        $propertyContent = new ODataPropertyContent();

        $links = [];
        foreach ($relProp as $prop) {
            $nuLink = new ODataLink();
            $propType = ResourcePropertyKind::RESOURCE_REFERENCE == $prop->getKind() ?
                'application/atom+xml;type=entry' : 'application/atom+xml;type=feed';
            $propName = $prop->getName();
            $nuLink->title = $propName;
            $nuLink->name = ODataConstants::ODATA_RELATED_NAMESPACE . $propName;
            $nuLink->url = $relativeUri . '/' . $propName;
            $nuLink->type = $propType;
            $links[] = $nuLink;
        }

        $odata = new ODataEntry();
        $odata->resourceSetName = $resourceSet->getName();
        $odata->id = $absoluteUri;
        $odata->title = $title;
        $odata->type = $type;
        $odata->propertyContent = $propertyContent;
        $odata->isMediaLinkEntry = $resourceType->isMediaLinkEntry();
        $odata->editLink = $relativeUri;
        $odata->mediaLink = $mediaLink;
        $odata->mediaLinks = $mediaLinks;
        $odata->links = $links;

        return $odata;
    }

    /**
     * Write top level feed element.
     *
     * @param array &$entryObjects Array of entry resources to be written
     *
     * @return ODataFeed
     */
    public function writeTopLevelElements(&$entryObjects)
    {
        // TODO: Implement writeTopLevelElements() method.
    }

    /**
     * Write top level url element.
     *
     * @param mixed $entryObject The entry resource whose url to be written
     *
     * @return ODataURL
     */
    public function writeUrlElement($entryObject)
    {
        // TODO: Implement writeUrlElement() method.
    }

    /**
     * Write top level url collection.
     *
     * @param array $entryObjects Array of entry resources
     *                            whose url to be written
     *
     * @return ODataURLCollection
     */
    public function writeUrlElements($entryObjects)
    {
        // TODO: Implement writeUrlElements() method.
    }

    /**
     * Write top level complex resource.
     *
     * @param mixed &$complexValue The complex object to be
     *                                    written
     * @param string $propertyName The name of the
     *                                    complex property
     * @param ResourceType &$resourceType Describes the type of
     *                                    complex object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelComplexObject(&$complexValue, $propertyName, ResourceType &$resourceType)
    {
        // TODO: Implement writeTopLevelComplexObject() method.
    }

    /**
     * Write top level bag resource.
     *
     * @param mixed &$BagValue The bag object to be
     *                                    written
     * @param string $propertyName The name of the
     *                                    bag property
     * @param ResourceType &$resourceType Describes the type of
     *                                    bag object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(&$BagValue, $propertyName, ResourceType &$resourceType)
    {
        // TODO: Implement writeTopLevelBagObject() method.
    }

    /**
     * Write top level primitive value.
     *
     * @param mixed &$primitiveValue The primitve value to be
     *                                            written
     * @param ResourceProperty &$resourceProperty Resource property
     *                                            describing the
     *                                            primitive property
     *                                            to be written
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelPrimitive(&$primitiveValue, ResourceProperty &$resourceProperty = null)
    {
        // TODO: Implement writeTopLevelPrimitive() method.
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest()
    {
        assert(null != $this->request, 'Request not yet set');

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

    protected function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        $typeName = $resourceType->getName();
        $keyProperties = $resourceType->getKeyProperties();
        assert(count($keyProperties) != 0, 'count($keyProperties) == 0');
        $keyString = $containerName . '(';
        $comma = null;
        foreach ($keyProperties as $keyName => $resourceProperty) {
            $keyType = $resourceProperty->getInstanceType();
            assert($keyType instanceof IType, '$keyType not instanceof IType');
            $keyName = $resourceProperty->getName();
            $keyValue = $entityInstance->$keyName;
            if (!isset($keyValue)) {
                throw ODataException::createInternalServerError(
                    Messages::badQueryNullKeysAreNotSupported($typeName, $keyName)
                );
            }

            $keyValue = $keyType->convertToOData($keyValue);
            $keyString .= $comma . $keyName . '=' . $keyValue;
            $comma = ',';
        }

        $keyString .= ')';

        return $keyString;

    }

    /**
     * @param $entryObject
     * @param $type
     * @param $relativeUri
     * @param $resourceType
     * @return array
     */
    protected function writeMediaData($entryObject, $type, $relativeUri, ResourceType $resourceType)
    {
        $context = $this->getService()->getOperationContext();
        $streamProviderWrapper = $this->getService()->getStreamProviderWrapper();
        assert(null != $streamProviderWrapper, "Retrieved stream provider must not be null");

        $mediaLink = null;
        if ($resourceType->isMediaLinkEntry()) {
            $eTag = $streamProviderWrapper->getStreamETag2($entryObject, null, $context);
            $mediaLink = new ODataMediaLink($type, '/$value', $relativeUri . '/$value', '*/*', $eTag);
        }
        $mediaLinks = [];
        if ($resourceType->hasNamedStream()) {
            $namedStreams = $resourceType->getAllNamedStreams();
            foreach ($namedStreams as $streamTitle => $resourceStreamInfo) {
                $readUri = $streamProviderWrapper->getReadStreamUri2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context,
                    $relativeUri
                );
                $mediaContentType = $streamProviderWrapper->getStreamContentType2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context
                );
                $eTag = $streamProviderWrapper->getStreamETag2(
                    $entryObject,
                    $resourceStreamInfo,
                    $context
                );

                $nuLink = new ODataMediaLink($streamTitle, $readUri, $readUri, $mediaContentType, $eTag);
                $mediaLinks[] = $nuLink;
            }
        }
        return [$mediaLink, $mediaLinks];
    }
}
