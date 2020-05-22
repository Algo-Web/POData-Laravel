<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\ODataNavigationPropertyInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\ObjectModel\IObjectSerialiser;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

class IronicSerialiser implements IObjectSerialiser
{
    use SerialiseDepWrapperTrait;
    use SerialisePropertyCacheTrait;
    use SerialiseNavigationTrait;
    use SerialiseNextPageLinksTrait;

    /**
     * Update time to insert into ODataEntry/ODataFeed fields.
     * @var Carbon
     */
    private $updated;

    /**
     * Has base URI already been written out during serialisation?
     * @var bool
     */
    private $isBaseWritten = false;

    /**
     * @param  IService                $service Reference to the data service instance
     * @param  RequestDescription|null $request Type instance describing the client submitted request
     * @throws \Exception
     */
    public function __construct(IService $service, RequestDescription $request = null)
    {
        $this->service                     = $service;
        $this->request                     = $request;
        $this->absoluteServiceUri          = $service->getHost()->getAbsoluteServiceUri()->getUrlAsString();
        $this->absoluteServiceUriWithSlash = rtrim($this->absoluteServiceUri, '/') . '/';
        $this->stack                       = new SegmentStack($request);
        $this->modelSerialiser             = new ModelSerialiser();
        $this->updated                     = Carbon::now();
    }

    /**
     * Write a top level entry resource.
     *
     * @param QueryResult $entryObject Reference to the entry object to be written
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @throws ODataException
     * @return ODataEntry|null
     */
    public function writeTopLevelElement(QueryResult $entryObject)
    {
        if (!isset($entryObject->results)) {
            array_pop($this->lightStack);
            return null;
        }
        SerialiserUtilities::checkSingleElementInput($entryObject);

        $this->loadStackIfEmpty();
        $baseURI             = $this->isBaseWritten ? null : $this->absoluteServiceUriWithSlash;
        $this->isBaseWritten = true;

        $stackCount = count($this->lightStack);
        $topOfStack = $this->lightStack[$stackCount-1];
        /** @var object $res */
        $res          = $entryObject->results;
        $payloadClass = get_class($res);
        /** @var ResourceEntityType $resourceType */
        $resourceType = $this->getService()->getProvidersWrapper()->resolveResourceType($topOfStack['type']);

        // need gubbinz to unpack an abstract resource type
        $resourceType = SerialiserUtilities::getConcreteTypeFromAbstractType(
            $resourceType,
            $this->getMetadata(),
            $payloadClass
        );

        // make sure we're barking up right tree
        if (!$resourceType instanceof ResourceEntityType) {
            throw new InvalidOperationException(get_class($resourceType));
        }

        /** @var Model $res */
        $res       = $entryObject->results;
        $targClass = $resourceType->getInstanceType()->getName();
        if (!($res instanceof $targClass)) {
            $msg = 'Object being serialised not instance of expected class, '
                   . $targClass . ', is actually ' . $payloadClass;
            throw new InvalidOperationException($msg);
        }

        $this->checkRelationPropertiesCached($targClass, $resourceType);
        /** @var ResourceProperty[] $relProp */
        $relProp = $this->propertiesCache[$targClass]['rel'];
        /** @var ResourceProperty[] $nonRelProp */
        $nonRelProp = $this->propertiesCache[$targClass]['nonRel'];

        $resourceSet = $resourceType->getCustomState();
        if (!$resourceSet instanceof ResourceSet) {
            throw new InvalidOperationException('');
        }
        $title = $resourceType->getName();
        $type  = $resourceType->getFullName();

        $relativeUri = SerialiserUtilities::getEntryInstanceKey(
            $res,
            $resourceType,
            $resourceSet->getName()
        );
        $absoluteUri = rtrim($this->absoluteServiceUri ?? '', '/') . '/' . $relativeUri;

        /** var $mediaLink ODataMediaLink|null */
        $mediaLink = null;
        /** var $mediaLinks ODataMediaLink[] */
        $mediaLinks = [];
        $this->writeMediaData(
            $res,
            $type,
            $relativeUri,
            $resourceType,
            $mediaLink,
            $mediaLinks
        );

        $propertyContent = SerialiserLowLevelWriters::writePrimitiveProperties(
            $res,
            $this->getModelSerialiser(),
            $nonRelProp
        );

        $links = $this->buildLinksFromRels($entryObject, $relProp, $relativeUri);

        $odata                   = new ODataEntry();
        $odata->resourceSetName  = $resourceSet->getName();
        $odata->setId($absoluteUri);
        $odata->setTitle(new ODataTitle($title));
        $odata->type             = new ODataCategory($type);
        $odata->propertyContent  = $propertyContent;
        $odata->isMediaLinkEntry = $resourceType->isMediaLinkEntry();
        $odata->editLink         = new ODataLink();
        $odata->editLink->setUrl($relativeUri);
        $odata->editLink->setName('edit');
        $odata->editLink->setTitle($title);
        $odata->mediaLink        = $mediaLink;
        $odata->mediaLinks       = $mediaLinks;
        $odata->links            = $links;
        $odata->setUpdated($this->getUpdated()->format(DATE_ATOM));
        $odata->setBaseURI($baseURI);

        $newCount = count($this->lightStack);
        if ($newCount != $stackCount) {
            $msg = 'Should have ' . $stackCount . ' elements in stack, have ' . $newCount . ' elements';
            throw new InvalidOperationException($msg);
        }
        $this->updateLightStack($newCount);
        return $odata;
    }

    /**
     * Write top level feed element.
     *
     * @param QueryResult $entryObjects Array of entry resources to be written
     *
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     * @return ODataFeed
     */
    public function writeTopLevelElements(QueryResult &$entryObjects)
    {
        SerialiserUtilities::checkElementsInput($entryObjects);

        $this->loadStackIfEmpty();

        /** @var string $title */
        $title       = $this->getRequest()->getContainerName();
        $relativeUri = $this->getRequest()->getIdentifier();
        $absoluteUri = $this->getRequest()->getRequestUrl()->getUrlAsString();

        $selfLink        = new ODataLink();
        $selfLink->setName('self');
        $selfLink->setTitle($relativeUri);
        $selfLink->setUrl($relativeUri);

        $odata               = new ODataFeed();
        $odata->setTitle(new ODataTitle($title));
        $odata->id           = $absoluteUri;
        $odata->setSelfLink($selfLink);
        $odata->setUpdated($this->getUpdated()->format(DATE_ATOM));
        $odata->setBaseURI($this->isBaseWritten ? null : $this->absoluteServiceUriWithSlash);
        $this->isBaseWritten = true;

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $odata->setRowCount($this->getRequest()->getCountValue());
        }
        $this->buildEntriesFromElements($entryObjects->results, $odata);

        $resourceSet = $this->getRequest()->getTargetResourceSetWrapper()->getResourceSet();
        $requestTop  = $this->getRequest()->getTopOptionCount();
        $pageSize    = $this->getService()->getConfiguration()->getEntitySetPageSize($resourceSet);
        $requestTop  = (null === $requestTop) ? $pageSize+1 : $requestTop;

        if (true == $entryObjects->hasMore && $requestTop > $pageSize) {
            $this->buildNextPageLink($entryObjects, $odata);
        }

        return $odata;
    }

    /**
     * Write top level url element.
     *
     * @param QueryResult $entryObject The entry resource whose url to be written
     *
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     * @return ODataURL
     */
    public function writeUrlElement(QueryResult $entryObject)
    {
        $url = new ODataURL('');
        /** @var Model|null $res */
        $res = $entryObject->results;
        if (null !== $res) {
            $currentResourceType = $this->getCurrentResourceSetWrapper()->getResourceType();
            $relativeUri         = SerialiserUtilities::getEntryInstanceKey(
                $res,
                $currentResourceType,
                $this->getCurrentResourceSetWrapper()->getName()
            );

            $url->setUrl(rtrim($this->absoluteServiceUri, '/') . '/' . $relativeUri);
        }

        return $url;
    }

    /**
     * Write top level url collection.
     *
     * @param QueryResult $entryObjects Array of entry resources whose url to be written
     *
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     * @return ODataURLCollection
     */
    public function writeUrlElements(QueryResult $entryObjects)
    {
        $urls = new ODataURLCollection();
        if (!empty($entryObjects->results)) {
            $i = 0;
            $lines = [];
            foreach ($entryObjects->results as $entryObject) {
                if (!$entryObject instanceof QueryResult) {
                    $query          = new QueryResult();
                    $query->results = $entryObject;
                } else {
                    $query = $entryObject;
                }
                $lines[$i] = $this->writeUrlElement($query);
                ++$i;
            }
            $urls->setUrls($lines);

            if ($i > 0 && true === $entryObjects->hasMore) {
                $this->buildNextPageLink($entryObjects, $urls);
            }
        }

        if ($this->getRequest()->queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $urls->setCount(intval($this->getRequest()->getCountValue()));
        }

        return $urls;
    }

    /**
     * Write top level complex resource.
     *
     * @param QueryResult  $complexValue  The complex object to be written
     * @param string       $propertyName  The name of the complex property
     * @param ResourceType $resourceType  Describes the type of complex object
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return ODataPropertyContent
     */
    public function writeTopLevelComplexObject(QueryResult &$complexValue, $propertyName, ResourceType &$resourceType)
    {
        /** @var object $result */
        $result = $complexValue->results;

        $propertyContent         = new ODataPropertyContent([]);
        $odataProperty           = new ODataProperty($propertyName, $resourceType->getFullName(), null);
        if (null != $result) {
            $internalContent     = SerialiserLowLevelWriters::writeComplexValue($resourceType, $result);
            $odataProperty->setValue($internalContent);
        }

        $propertyContent[$propertyName] = $odataProperty;

        return $propertyContent;
    }

    /**
     * Write top level bag resource.
     *
     * @param QueryResult  $BagValue      The bag object to be
     *                                    written
     * @param string       $propertyName  The name of the
     *                                    bag property
     * @param ResourceType $resourceType  Describes the type of
     *                                    bag object
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(QueryResult &$BagValue, $propertyName, ResourceType &$resourceType)
    {
        /** @var mixed[]|null $result */
        $result = $BagValue->results;

        $propertyContent         = new ODataPropertyContent([]);
        $odataProperty           = new ODataProperty($propertyName, null, null);
        $odataProperty->setTypeName('Collection(' . $resourceType->getFullName() . ')');
        $odataProperty->setValue(SerialiserLowLevelWriters::writeBagValue($resourceType, $result));

        $propertyContent[$propertyName] = $odataProperty;
        return $propertyContent;
    }

    /**
     * Write top level primitive value.
     *
     * @param  QueryResult               $primitiveValue    The primitive value to be
     *                                                      written
     * @param  ResourceProperty          $resourceProperty  Resource property describing the
     *                                                      primitive property to be written
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return ODataPropertyContent
     */
    public function writeTopLevelPrimitive(QueryResult &$primitiveValue, ResourceProperty &$resourceProperty = null)
    {
        if (null === $resourceProperty) {
            throw new InvalidOperationException('Resource property must not be null');
        }
        $propertyContent = new ODataPropertyContent([]);

        $odataProperty       = new ODataProperty($resourceProperty->getName(), null, null);
        $iType               = $resourceProperty->getInstanceType();
        if (!$iType instanceof IType) {
            throw new InvalidOperationException(get_class($iType));
        }
        $odataProperty->setTypeName($iType->getFullTypeName());
        $value = null;
        if (null != $primitiveValue->results) {
            $rType = $resourceProperty->getResourceType()->getInstanceType();
            if (!$rType instanceof IType) {
                throw new InvalidOperationException(get_class($rType));
            }
            $value = SerialiserLowLevelWriters::primitiveToString($rType, $primitiveValue->results);
        }
        $odataProperty->setValue($value);

        $propertyContent[$odataProperty->getName()] = $odataProperty;

        return $propertyContent;
    }

    /**
     * Get update timestamp.
     *
     * @return Carbon
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param  mixed                     $entryObject
     * @param  string                    $type
     * @param  string                    $relativeUri
     * @param  ResourceType              $resourceType
     * @param  ODataMediaLink|null       $mediaLink
     * @param  ODataMediaLink[]          $mediaLinks
     * @throws InvalidOperationException
     * @return void
     */
    protected function writeMediaData(
        $entryObject,
        $type,
        $relativeUri,
        ResourceType $resourceType,
        ODataMediaLink &$mediaLink = null,
        array &$mediaLinks = []
    ) {
        $context               = $this->getService()->getOperationContext();
        $streamProviderWrapper = $this->getService()->getStreamProviderWrapper();
        if (null == $streamProviderWrapper) {
            throw new InvalidOperationException('Retrieved stream provider must not be null');
        }

        /** @var ODataMediaLink|null $mediaLink */
        $mediaLink = null;
        if ($resourceType->isMediaLinkEntry()) {
            $eTag      = $streamProviderWrapper->getStreamETag2($entryObject, $context, null);
            $mediaLink = new ODataMediaLink($type, '/$value', $relativeUri . '/$value', '*/*', $eTag, 'edit-media');
        }
        /** @var ODataMediaLink[] $mediaLinks */
        $mediaLinks = [];
        if ($resourceType->hasNamedStream()) {
            $namedStreams = $resourceType->getAllNamedStreams();
            foreach ($namedStreams as $streamTitle => $resourceStreamInfo) {
                $readUri = $streamProviderWrapper->getReadStreamUri2(
                    $entryObject,
                    $context,
                    $resourceStreamInfo,
                    $relativeUri
                );
                $mediaContentType = $streamProviderWrapper->getStreamContentType2(
                    $entryObject,
                    $context,
                    $resourceStreamInfo
                );
                $eTag = $streamProviderWrapper->getStreamETag2(
                    $entryObject,
                    $context,
                    $resourceStreamInfo
                );

                $nuLink       = new ODataMediaLink($streamTitle, $readUri, $readUri, $mediaContentType, $eTag);
                $mediaLinks[] = $nuLink;
            }
        }
    }

    /**
     * @param  QueryResult               $entryObject
     * @param  ResourceProperty          $prop
     * @param  ODataLink                 $nuLink
     * @param  ResourcePropertyKind      $propKind
     * @param  string                    $propName
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     */
    private function expandNavigationProperty(
        QueryResult $entryObject,
        ResourceProperty $prop,
        ODataLink $nuLink,
        ResourcePropertyKind $propKind,
        string $propName
    ): void {
        $nextName             = $prop->getResourceType()->getName();
        $value                = $entryObject->results->{$propName};
        $isCollection         = ResourcePropertyKind::RESOURCESET_REFERENCE() == $propKind;
        $nuLink->setIsCollection($isCollection);

        if (is_array($value)) {
            if (1 == count($value) && !$isCollection) {
                $value = $value[0];
            } else {
                $value = collect($value);
            }
        }

        $result          = new QueryResult();
        $result->results = $value;
        $nullResult      = null === $value;
        $isSingleton     = $value instanceof Model;
        $resultCount     = $nullResult ? 0 : ($isSingleton ? 1 : $value->count());

        if (0 < $resultCount) {
            $newStackLine = ['type' => $nextName, 'prop' => $propName, 'count' => $resultCount];
            array_push($this->lightStack, $newStackLine);
            if (!$isCollection) {
                $type           = 'application/atom+xml;type=entry';
                $expandedResult = $this->writeTopLevelElement($result);
            } else {
                $type           = 'application/atom+xml;type=feed';
                $expandedResult = $this->writeTopLevelElements($result);
            }
            $nuLink->setType($type);
            $nuLink->setExpandedResult(new ODataExpandedResult($expandedResult));
        } else {
            /** @var ResourceType $type */
            $type = $this->getService()->getProvidersWrapper()->resolveResourceType($nextName);
            if (!$isCollection) {
                $result                  = new ODataEntry();
                $result->resourceSetName = $type->getName();
            } else {
                $result                 = new ODataFeed();
                $result->selfLink       = new ODataLink(ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE);
            }
            $nuLink->setExpandedResult(new ODataExpandedResult($result));
        }
        if (null !== $nuLink->getExpandedResult() && null !== $nuLink->getExpandedResult()->getData()->getSelfLink()) {
            $url                                     = $nuLink->getUrl();
            $raw                                     = $nuLink->getExpandedResult()->getData();

            $raw->getSelfLink()->setTitle($propName);
            $raw->getSelfLink()->setUrl($url);
            $raw->setTitle(new ODataTitle($propName));
            $raw->setId(rtrim($this->absoluteServiceUri ?? '', '/') . '/' . $url);

            if ($raw instanceof ODataEntry) {
                $nuLink->getExpandedResult()->setEntry($raw);
            } else {
                $nuLink->getExpandedResult()->setFeed($raw);
            }
        }
    }

    /**
     * @param  QueryResult               $entryObject
     * @param  ResourceProperty[]        $relProp
     * @param  string                    $relativeUri
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     * @return ODataLink[]
     */
    protected function buildLinksFromRels(QueryResult $entryObject, array $relProp, string $relativeUri): array
    {
        $links = [];
        foreach ($relProp as $prop) {
            $nuLink   = new ODataLink();
            /** @var ResourcePropertyKind|int $propKind */
            $propKind = $prop->getKind();

            if (!(ResourcePropertyKind::RESOURCESET_REFERENCE() == $propKind
                  || ResourcePropertyKind::RESOURCE_REFERENCE() == $propKind)) {
                $msg = '$propKind != ResourcePropertyKind::RESOURCESET_REFERENCE &&'
                       . ' $propKind != ResourcePropertyKind::RESOURCE_REFERENCE';
                throw new InvalidOperationException($msg);
            }
            $propTail             = ResourcePropertyKind::RESOURCE_REFERENCE() == $propKind ? 'entry' : 'feed';
            $propType             = 'application/atom+xml;type=' . $propTail;
            $propName             = $prop->getName();
            $nuLink->setTitle($propName);
            $nuLink->setName(ODataConstants::ODATA_RELATED_NAMESPACE . $propName);
            $nuLink->setUrl($relativeUri . '/' . $propName);
            $nuLink->setType($propType);
            $nuLink->setIsCollection('feed' === $propTail);

            $shouldExpand = $this->shouldExpandSegment($propName);

            if ($shouldExpand) {
                $this->expandNavigationProperty($entryObject, $prop, $nuLink, $propKind, $propName);
            }
            $nuLink->setIsExpanded(null !== ($nuLink->getExpandedResult()));
            $links[]            = $nuLink;
        }
        return $links;
    }

    /**
     * @param  object[]|Collection       $res
     * @param  ODataFeed                 $odata
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     */
    protected function buildEntriesFromElements($res, ODataFeed $odata): void
    {
        foreach ($res as $entry) {
            if (!$entry instanceof QueryResult) {
                $query          = new QueryResult();
                $query->results = $entry;
            } else {
                $query = $entry;
            }
            $odata->addEntry($this->writeTopLevelElement($query));
        }
        //$odata->setEntries($entries);
    }
}
