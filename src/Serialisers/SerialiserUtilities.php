<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16/02/20
 * Time: 1:19 PM.
 */
namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Query\QueryResult;

abstract class SerialiserUtilities
{
    /**
     * @param  int  $resourceKind
     * @return bool
     */
    public static function isMatchPrimitive($resourceKind)
    {
        if (16 > $resourceKind) {
            return false;
        }
        if (28 < $resourceKind) {
            return false;
        }
        return 0 == ($resourceKind % 4);
    }

    /**
     * @param  QueryResult               $entryObjects
     * @throws InvalidOperationException
     */
    public static function checkElementsInput(QueryResult &$entryObjects)
    {
        $res = $entryObjects->results;
        if (!(is_array($res) || $res instanceof Collection)) {
            throw new InvalidOperationException('!is_array($entryObjects->results)');
        }
        if (is_array($res) && 0 == count($res)) {
            $entryObjects->hasMore = false;
        }
        if ($res instanceof Collection && 0 == $res->count()) {
            $entryObjects->hasMore = false;
        }
    }

    /**
     * @param  QueryResult               $entryObject
     * @throws InvalidOperationException
     */
    public static function checkSingleElementInput(QueryResult $entryObject)
    {
        if (!$entryObject->results instanceof Model) {
            $res = $entryObject->results;
            $msg = is_array($res) ? 'Entry object must be single Model' : get_class($res);
            throw new InvalidOperationException($msg);
        }
    }

    /**
     * @param  Model                     $entityInstance
     * @param  ResourceType              $resourceType
     * @param  string                    $containerName
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     * @return string
     */
    public static function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        $typeName = $resourceType->getName();
        $keyProperties = $resourceType->getKeyProperties();
        if (0 == count($keyProperties)) {
            throw new InvalidOperationException('count($keyProperties) == 0');
        }
        $keyString = $containerName . '(';
        $comma = null;
        foreach ($keyProperties as $keyName => $resourceProperty) {
            $keyType = $resourceProperty->getInstanceType();
            if (!$keyType instanceof IType) {
                throw new InvalidOperationException('$keyType not instanceof IType');
            }
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
     * @param  ResourceEntityType              $resourceType
     * @param  IMetadataProvider               $metadata
     * @param  string                          $payloadClass
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return ResourceEntityType|ResourceType
     */
    public static function getConcreteTypeFromAbstractType(
        ResourceEntityType $resourceType,
        IMetadataProvider $metadata,
        $payloadClass
    ) {
        if ($resourceType->isAbstract()) {
            $derived = $metadata->getDerivedTypes($resourceType);
            if (0 == count($derived)) {
                throw new InvalidOperationException('Supplied abstract type must have at least one derived type');
            }
            $derived = array_filter(
                $derived,
                function (ResourceType $element) {
                    return !$element->isAbstract();
                }
            );
            foreach ($derived as $rawType) {
                $name = $rawType->getInstanceType()->getName();
                if ($payloadClass == $name) {
                    $resourceType = $rawType;
                    break;
                }
            }
        }
        // despite all set up, checking, etc, if we haven't picked a concrete resource type,
        // wheels have fallen off, so blow up
        if ($resourceType->isAbstract()) {
            throw new InvalidOperationException('Concrete resource type not selected for payload ' . $payloadClass);
        }
        return $resourceType;
    }
}
