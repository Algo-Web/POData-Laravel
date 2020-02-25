<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26/02/20
 * Time: 1:42 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraHasManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Database\Eloquent\Model;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\QueryType;

class LaravelReadQueryTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testEagerLoadCollisionResolved()
    {
        $model = new OrchestraHasManyTestModel();
        $model->setEagerLoad(['parent']);

        $combo = ['parent', 'children'];

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('applyFiltering')->withArgs([$model, true, $combo, PHP_INT_MAX, 0, null])
            ->andThrow(InvalidOperationException::class);

        $input = function (ActionVerb $type, string $class, OrchestraHasManyTestModel $model) {
            return ActionVerb::READ() == $type && OrchestraHasManyTestModel::class == $class;
        };

        $foo->shouldReceive('getAuth->canAuth')
            ->withArgs($input)
            ->andReturn(true)->once();

        $type = QueryType::ENTITIES();
        $rSet = m::mock(ResourceSet::class);

        $this->expectException(InvalidOperationException::class);

        $foo->getResourceSet($type, $rSet, null, null, null, null, null, $combo, $model);
    }
}
