<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16/02/20
 * Time: 12:12 AM
 */

namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Database\Eloquent\Model;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\IType;

trait SerialiseLowLevelWritersTrait
{
    /**
     * Collection of complex type instances used for cycle detection.
     *
     * @var array
     */
    protected $complexTypeInstanceCollection;

    /**
     * @param $entryObject
     * @param $nonRelProp
     * @return ODataPropertyContent
     * @throws InvalidOperationException
     */
    protected function writePrimitiveProperties(Model $entryObject, $nonRelProp)
    {
        $propertyContent = new ODataPropertyContent();
        $cereal = $this->getModelSerialiser()->bulkSerialise($entryObject);
        $cereal = array_intersect_key($cereal, $nonRelProp);

        foreach ($cereal as $corn => $flake) {
            $corn = strval($corn);
            $rType = $nonRelProp[$corn]['type'];
            /** @var ResourceProperty $nrp */
            $nrp = $nonRelProp[$corn]['prop'];
            $subProp = new ODataProperty();
            $subProp->name = $corn;
            $subProp->value = isset($flake) ? $this->primitiveToString($rType, $flake) : null;
            $subProp->typeName = $nrp->getResourceType()->getFullName();
            $propertyContent->properties[$corn] = $subProp;
        }
        return $propertyContent;
    }

    /**
     * @param ResourceType $resourceType
     * @param $result
     * @return ODataBagContent|null
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    protected function writeBagValue(ResourceType &$resourceType, $result)
    {
        if (!(null == $result || is_array($result))) {
            throw new InvalidOperationException('Bag parameter must be null or array');
        }
        $typeKind = $resourceType->getResourceTypeKind();
        $kVal = $typeKind;
        if (!(ResourceTypeKind::PRIMITIVE() == $kVal || ResourceTypeKind::COMPLEX() == $kVal)) {
            $msg = '$bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE'
                   .' && $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX';
            throw new InvalidOperationException($msg);
        }
        if (null == $result) {
            return null;
        }
        $bag = new ODataBagContent();
        $result = array_filter($result);
        foreach ($result as $value) {
            if (ResourceTypeKind::PRIMITIVE() == $kVal) {
                $instance = $resourceType->getInstanceType();
                if (!$instance instanceof IType) {
                    throw new InvalidOperationException(get_class($instance));
                }
                $bag->propertyContents[] = $this->primitiveToString($instance, $value);
            } elseif (ResourceTypeKind::COMPLEX() == $kVal) {
                $bag->propertyContents[] = $this->writeComplexValue($resourceType, $value);
            }
        }
        return $bag;
    }

    /**
     * @param  ResourceType         $resourceType
     * @param  object               $result
     * @param  string|null          $propertyName
     * @return ODataPropertyContent
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    protected function writeComplexValue(ResourceType &$resourceType, &$result, $propertyName = null)
    {
        if (!is_object($result)) {
            throw new InvalidOperationException('Supplied $customObject must be an object');
        }

        $count = count($this->complexTypeInstanceCollection);
        for ($i = 0; $i < $count; ++$i) {
            if ($this->complexTypeInstanceCollection[$i] === $result) {
                throw new InvalidOperationException(
                    Messages::objectModelSerializerLoopsNotAllowedInComplexTypes($propertyName)
                );
            }
        }

        $this->complexTypeInstanceCollection[$count] = &$result;

        $internalContent = new ODataPropertyContent();
        $resourceProperties = $resourceType->getAllProperties();
        // first up, handle primitive properties
        foreach ($resourceProperties as $prop) {
            $resourceKind = $prop->getKind();
            $propName = $prop->getName();
            $internalProperty = new ODataProperty();
            $internalProperty->name = $propName;
            if (static::isMatchPrimitive($resourceKind)) {
                $iType = $prop->getInstanceType();
                if (!$iType instanceof IType) {
                    throw new InvalidOperationException(get_class($iType));
                }
                $internalProperty->typeName = $iType->getFullTypeName();

                $rType = $prop->getResourceType()->getInstanceType();
                if (!$rType instanceof IType) {
                    throw new InvalidOperationException(get_class($rType));
                }

                $internalProperty->value = $this->primitiveToString($rType, $result->$propName);

                $internalContent->properties[$propName] = $internalProperty;
            } elseif (ResourcePropertyKind::COMPLEX_TYPE == $resourceKind) {
                $rType = $prop->getResourceType();
                $internalProperty->typeName = $rType->getFullName();
                $internalProperty->value = $this->writeComplexValue($rType, $result->$propName, $propName);

                $internalContent->properties[$propName] = $internalProperty;
            }
        }

        unset($this->complexTypeInstanceCollection[$count]);
        return $internalContent;
    }

    /**
     * @return ModelSerialiser
     */
    abstract public function getModelSerialiser();
}
