<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\QueryType;

class LaravelReadQueryTest extends TestCase
{
    public function testBadSkipToken()
    {
        $expected = 'Skip token must be either null or instance of SkipTokenInfo.';
        $actual = null;

        $query = m::mock(QueryType::class);
        $resource = m::mock(ResourceSet::class);
        $skipToken = new \DateTime();

        $foo = new LaravelReadQuery();

        try {
            $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}