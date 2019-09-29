<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/09/19
 * Time: 6:08 PM
 */

namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Database\Eloquent\Model;
use POData\Common\ODataException;

class LaravelWriteQuery extends LaravelBaseQuery
{


    /**
     * @param $data
     * @param $paramList
     * @param Model|null $sourceEntityInstance
     * @return array
     */
    public function createUpdateDeleteProcessInput($data, $paramList, Model $sourceEntityInstance)
    {
        $parms = [];

        foreach ($paramList as $spec) {
            $varType = isset($spec['type']) ? $spec['type'] : null;
            $varName = $spec['name'];
            if (null == $varType) {
                $parms[] = ('id' == $varName) ? $sourceEntityInstance->getKey() : $sourceEntityInstance->$varName;
                continue;
            }
            // TODO: Give this smarts and actively pick up instantiation details
            $var = new $varType();
            if ($spec['isRequest']) {
                $var->setMethod('POST');
                $var->request = new \Symfony\Component\HttpFoundation\ParameterBag($data);
            }
            $parms[] = $var;
        }
        return $parms;
    }


    /**
     * @param $result
     * @throws ODataException
     * @return array|mixed
     */
    public function createUpdateDeleteProcessOutput($result)
    {
        if (!($result instanceof \Illuminate\Http\JsonResponse)) {
            throw ODataException::createInternalServerError('Controller response not well-formed json.');
        }
        $outData = $result->getData();
        if (is_object($outData)) {
            $outData = (array) $outData;
        }

        if (!is_array($outData)) {
            throw ODataException::createInternalServerError('Controller response does not have an array.');
        }
        if (!(key_exists('id', $outData) && key_exists('status', $outData) && key_exists('errors', $outData))) {
            throw ODataException::createInternalServerError(
                'Controller response array missing at least one of id, status and/or errors fields.'
            );
        }
        return $outData;
    }
}
