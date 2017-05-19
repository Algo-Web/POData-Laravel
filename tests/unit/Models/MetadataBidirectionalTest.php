<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery as m;

class MetadataBidirectionalTest extends TestCase
{
    public function testMonomorphicSourceHooks()
    {
        $foo = new TestMonomorphicSource();
        $targ = TestMonomorphicTarget::class;

        $expected = [
            'many_source' => [ 'target' => $targ, 'property' => 'manySource', 'local' => 'many_id'],
            'one_source' => [ 'target' => $targ, 'property' => 'oneSource', 'local' => 'one_id']
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
        $targ = TestMonomorphicSource::class;

        $expected = [
            'many_id' => [ 'target' => $targ, 'property' => 'manyTarget', 'local' => 'many_source'],
            'one_id' => [ 'target' => $targ, 'property' => 'oneTarget', 'local' => 'one_source']
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

    public function testMonomorphicManyToMany()
    {
        $foo = new TestMonomorphicManySource();
        $fooTarg = TestMonomorphicManyTarget::class;
        $bar = new TestMonomorphicManyTarget();
        $barTarg = TestMonomorphicManySource::class;

        $expectedFoo = [ 'many_source' => [ 'target' => $fooTarg, 'property' => 'manySource', 'local' => 'many_id']];
        $expectedBar = [ 'many_id' => [ 'target' => $barTarg, 'property' => 'manyTarget', 'local' => 'many_source']];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));

        foreach ($expectedFoo as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expectedFoo[$key], $actual[$key]);
        }

        $actual = $bar->getRelationships();
        foreach ($expectedBar as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expectedBar[$key], $actual[$key]);
        }
    }
}
