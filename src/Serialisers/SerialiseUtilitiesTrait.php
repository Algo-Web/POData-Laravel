<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16/02/20
 * Time: 1:19 PM
 */

namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Query\QueryResult;

trait SerialiseUtilitiesTrait
{
    /**
     * @param int $resourceKind
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
     * @param QueryResult $entryObjects
     * @throws InvalidOperationException
     */
    protected function checkElementsInput(QueryResult &$entryObjects)
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
     * @param QueryResult $entryObject
     * @throws InvalidOperationException
     */
    protected function checkSingleElementInput(QueryResult $entryObject)
    {
        if (!$entryObject->results instanceof Model) {
            $res = $entryObject->results;
            $msg = is_array($res) ? 'Entry object must be single Model' : get_class($res);
            throw new InvalidOperationException($msg);
        }
    }

    /**
     * @param Model $entityInstance
     * @param ResourceType $resourceType
     * @param string $containerName
     * @return string
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     */
    protected function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
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
}
