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
use POData\Providers\Query\QueryResult;

trait SerialiseUtilitiesTrait
{
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
}
