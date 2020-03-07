<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16/02/20
 * Time: 1:18 AM.
 */
namespace AlgoWeb\PODataLaravel\Serialisers;

use POData\Common\InvalidOperationException;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Query\QueryResult;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

trait SerialiseNextPageLinksTrait
{
    /**
     * @param  QueryResult                  $entryObjects
     * @param  ODataURLCollection|ODataFeed $odata
     * @throws InvalidOperationException
     * @throws ODataException
     */
    protected function buildNextPageLink(QueryResult $entryObjects, $odata): void
    {
        $stackSegment = $this->getRequest()->getTargetResourceSetWrapper()->getName();
        /** @var mixed[] $res */
        $res                 = $entryObjects->results;
        $lastObject          = end($res);
        $segment             = $this->getNextLinkUri($lastObject);
        $nextLink            = new ODataLink();
        $nextLink->name      = ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING;
        $nextLink->url       = rtrim($this->absoluteServiceUri, '/') . '/' . $stackSegment . $segment;
        $odata->nextPageLink = $nextLink;
    }

    /**
     * Wheter next link is needed for the current resource set (feed)
     * being serialized.
     *
     * @param int $resultSetCount Number of entries in the current
     *                            resource set
     *
     * @throws InvalidOperationException
     * @return bool                      true if the feed must have a next page link
     */
    protected function needNextPageLink(int $resultSetCount): bool
    {
        $currentResourceSet = $this->getCurrentResourceSetWrapper();
        $recursionLevel     = count($this->getStack()->getSegmentNames());
        $pageSize           = $currentResourceSet->getResourceSetPageSize();

        if (1 == $recursionLevel) {
            //presence of $top option affect next link for root container
            $topValueCount = $this->getRequest()->getTopOptionCount();
            if (null !== $topValueCount && ($topValueCount <= $pageSize)) {
                return false;
            }
        }
        return $resultSetCount == $pageSize;
    }


    /**
     * Get next page link from the given entity instance.
     *
     * @param  mixed                     $lastObject  Last object serialized to be
     *                                                used for generating
     *                                                $skiptoken
     * @throws ODataException
     * @throws InvalidOperationException
     * @return string                    for the link for next page
     */
    protected function getNextLinkUri(&$lastObject)
    {
        /** @var RootProjectionNode|ExpandedProjectionNode $currentExpandedProjectionNode */
        $currentExpandedProjectionNode = $this->getCurrentExpandedProjectionNode();
        $internalOrderByInfo           = $currentExpandedProjectionNode->getInternalOrderByInfo();
        if (null === $internalOrderByInfo) {
            throw new InvalidOperationException('Null');
        }
        if (!$internalOrderByInfo instanceof InternalOrderByInfo) {
            throw new InvalidOperationException(get_class($internalOrderByInfo));
        }
        $numSegments          = count($internalOrderByInfo->getOrderByPathSegments());
        $queryParameterString = $this->getNextPageLinkQueryParametersForRootResourceSet();

        $skipToken = $internalOrderByInfo->buildSkipTokenValue($lastObject);
        if (empty($skipToken)) {
            throw new InvalidOperationException('!is_null($skipToken)');
        }
        $token     = (1 < $numSegments) ? '$skiptoken=' : '$skip=';
        $skipToken = (1 < $numSegments) ? $skipToken : intval(trim($skipToken, '\''));
        $skipToken = '?' . $queryParameterString . $token . $skipToken;

        return $skipToken;
    }

    /**
     * Builds the string corresponding to query parameters for top level results
     * (result set identified by the resource path) to be put in next page link.
     *
     * @throws InvalidOperationException
     * @return string|null               string representing the query parameters in the URI
     *                                   query parameter format, NULL if there
     *                                   is no query parameters
     *                                   required for the next link of top level result set
     */
    protected function getNextPageLinkQueryParametersForRootResourceSet(): ?string
    {
        /** @var string|null $queryParameterString */
        $queryParameterString = null;
        foreach ([ODataConstants::HTTPQUERY_STRING_FILTER,
            ODataConstants::HTTPQUERY_STRING_EXPAND,
            ODataConstants::HTTPQUERY_STRING_ORDERBY,
            ODataConstants::HTTPQUERY_STRING_INLINECOUNT,
            ODataConstants::HTTPQUERY_STRING_SELECT, ] as $queryOption) {
            /** @var string|null $value */
            $value = $this->getService()->getHost()->getQueryStringItem($queryOption);
            if (null !== $value) {
                if (null !== $queryParameterString) {
                    $queryParameterString = /* @scrutinizer ignore-type */$queryParameterString . '&';
                }

                $queryParameterString .= $queryOption . '=' . $value;
            }
        }

        $topCountValue = $this->getRequest()->getTopOptionCount();
        if (null !== $topCountValue) {
            $remainingCount = $topCountValue - $this->getRequest()->getTopCount();
            if (0 < $remainingCount) {
                if (null !== $queryParameterString) {
                    $queryParameterString .= '&';
                }

                $queryParameterString .= ODataConstants::HTTPQUERY_STRING_TOP . '=' . $remainingCount;
            }
        }

        if (null !== $queryParameterString) {
            $queryParameterString .= '&';
        }

        return $queryParameterString;
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @throws InvalidOperationException
     * @return RequestDescription
     */
    abstract public function getRequest();

    /**
     * Gets the segment stack instance.
     *
     * @return SegmentStack
     */
    abstract public function getStack();

    /**
     * Resource set wrapper for the resource being serialized.
     *
     * @throws InvalidOperationException
     * @return ResourceSetWrapper
     */
    abstract protected function getCurrentResourceSetWrapper();

    /**
     * Find a 'ExpandedProjectionNode' instance in the projection tree
     * which describes the current segment.
     *
     * @throws InvalidOperationException
     * @return null|RootProjectionNode|ExpandedProjectionNode
     */
    abstract protected function getCurrentExpandedProjectionNode();

    /**
     * Gets the data service instance.
     *
     * @return IService
     */
    abstract public function getService();
}
