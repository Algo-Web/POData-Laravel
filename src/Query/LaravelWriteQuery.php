<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/09/19
 * Time: 6:08 PM
 */

namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Database\Eloquent\Model;

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
}
