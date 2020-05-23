<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 28/02/20
 * Time: 12:13 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers;

use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use POData\ObjectModel\ODataMediaLink;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryResult;

class DummyIronicSerialiser extends IronicSerialiser
{
    public function getLightStack(): array
    {
        return parent::getLightStack();
    }

    public function loadStackIfEmpty(): void
    {
        parent::loadStackIfEmpty();
    }

    public function getNextLinkUri(&$lastObject)
    {
        return parent::getNextLinkUri($lastObject);
    }

    public function getNextPageLinkQueryParametersForRootResourceSet(): ?string
    {
        return parent::getNextPageLinkQueryParametersForRootResourceSet();
    }

    public function buildLinksFromRels(QueryResult $entryObject, array $relProp, string $relativeUri): array
    {
        return parent::buildLinksFromRels($entryObject, $relProp, $relativeUri);
    }

    public function writeMediaData(
        $entryObject,
        $type,
        $relativeUri,
        ResourceType $resourceType,
        ODataMediaLink &$mediaLink = null,
        array &$mediaLinks = []
    ) {
        parent::writeMediaData($entryObject, $type, $relativeUri, $resourceType, $mediaLink, $mediaLinks);
    }
}
