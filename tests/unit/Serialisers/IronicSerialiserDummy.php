<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

class IronicSerialiserDummy extends IronicSerialiser
{
    public function getCurrentExpandedProjectionNode()
    {
        return parent::getCurrentExpandedProjectionNode();
    }

    public function shouldExpandSegment($navigationPropertyName)
    {
        return parent::shouldExpandSegment($navigationPropertyName);
    }

    public function getProjectionNodes()
    {
        return parent::getProjectionNodes();
    }

    public function needNextPageLink($resultSetCount)
    {
        return parent::needNextPageLink($resultSetCount);
    }

    public function getNextLinkUri(&$lastObject, $absoluteUri)
    {
        return parent::getNextLinkUri($lastObject, $absoluteUri);
    }
}
