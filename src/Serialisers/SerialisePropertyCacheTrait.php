<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/02/20
 * Time: 6:43 PM.
 */
namespace AlgoWeb\PODataLaravel\Serialisers;

use POData\Providers\Metadata\ResourceEntityType;

trait SerialisePropertyCacheTrait
{
    /** @var array[] */
    protected $propertiesCache = [];

    /**
     * @param  string               $targClass
     * @param  ResourceEntityType   $resourceType
     * @throws \ReflectionException
     * @return void
     */
    protected function checkRelationPropertiesCached(string $targClass, ResourceEntityType $resourceType)
    {
        if (!array_key_exists($targClass, $this->propertiesCache)) {
            $rawProp    = $resourceType->getAllProperties();
            $relProp    = [];
            $nonRelProp = [];
            foreach ($rawProp as $prop) {
                $propType = $prop->getResourceType();
                if ($propType instanceof ResourceEntityType) {
                    $relProp[] = $prop;
                } else {
                    $nonRelProp[$prop->getName()] = ['prop' => $prop, 'type' => $propType->getInstanceType()];
                }
            }
            $this->propertiesCache[$targClass] = ['rel' => $relProp, 'nonRel' => $nonRelProp];
        }
    }
}
