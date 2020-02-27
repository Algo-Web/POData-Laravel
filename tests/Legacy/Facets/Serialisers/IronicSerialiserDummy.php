<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Serialisers;

use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use AlgoWeb\PODataLaravel\Serialisers\SerialiserUtilities;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;

class IronicSerialiserDummy extends IronicSerialiser
{
    protected $expand = [];

    public function getCurrentExpandedProjectionNode()
    {
        return parent::getCurrentExpandedProjectionNode();
    }

    public function shouldExpandSegment(string $navigationPropertyName)
    {
        if (array_key_exists($navigationPropertyName, $this->expand)) {
            return $this->expand[$navigationPropertyName];
        }

        return parent::shouldExpandSegment($navigationPropertyName);
    }

    public function getProjectionNodes()
    {
        return parent::getProjectionNodes();
    }

    public function needNextPageLink(int $resultSetCount)
    {
        return parent::needNextPageLink($resultSetCount);
    }

    public function getNextLinkUri(&$lastObject)
    {
        return parent::getNextLinkUri($lastObject);
    }

    public function setLightStack(array $stack)
    {
        $this->lightStack = $stack;
    }

    public function setPropertyExpansion($propName, $toExpand = true)
    {
        $this->expand[$propName] = boolval($toExpand);
    }
    
    public function getConcreteTypeFromAbstractType(
        ResourceEntityType $resourceType,
        IMetadataProvider $metadata,
        $payloadClass
    ) {
        return SerialiserUtilities::getConcreteTypeFromAbstractType($resourceType, $metadata, $payloadClass);
    }
}
