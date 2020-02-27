<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 10:20 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Query;

use AlgoWeb\PODataLaravel\Query\LaravelBulkQuery;
use Illuminate\Http\JsonResponse;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceSet;

class DummyBulkQuery extends LaravelBulkQuery
{
    public function prepareBulkRequestInput(array $paramList, array $data, array $keyDescriptors = null)
    {
        return parent::prepareBulkRequestInput($paramList, $data, $keyDescriptors);
    }

    public function processBulkCustom(
        ResourceSet $sourceResourceSet,
        array $data,
        array $mapping,
        string $pastVerb,
        array $keyDescriptor = null
    ) {
        return parent::processBulkCustom(
            $sourceResourceSet,
            $data,
            $mapping,
            $pastVerb,
            $keyDescriptor
        );
    }

    /**
     * @param $result
     * @throws ODataException
     * @return array|mixed
     */
    public function createUpdateDeleteProcessOutput(JsonResponse $result)
    {
        return parent::createUpdateDeleteProcessOutput($result);
    }
}
