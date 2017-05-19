<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery as m;

class MetadataBidirectionalTest extends TestCase
{
    public function testMonomorphicSourceHooks()
    {
        $foo = new TestMonomorphicSource();

        $expected = [
            'many_source' => [ 'property' => 'manySource', 'local' => 'many_id'],
            'one_source' => [ 'property' => 'oneSource', 'local' => 'one_id']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicTargetHooks()
    {
        $foo = new TestMonomorphicTarget();

        $expected = [
            'many_id' => [ 'property' => 'manyTarget', 'local' => 'many_source'],
            'one_id' => [ 'property' => 'oneTarget', 'local' => 'one_source']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }
}
