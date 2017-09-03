<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;
use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetAlternate;
use AlgoWeb\PODataLaravel\Models\TestPolymorphicDualSource;
use Illuminate\Support\Facades\App;
use Mockery as m;

class MetadataProviderNewCharacterisationTest extends TestCase
{
    public function testGetPolymorphicOneToOne()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->reset();
        $bar = m::mock(MetadataProviderNew::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $bar->reset();
        $classen = [TestMorphOneSource::class, TestMorphOneSourceAlternate::class, TestMorphTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getRelationHolder')->andReturn($holder);

        $expected = $foo->getRepairedRoundTripRelations();
        $foo->reset();
        $actual = $bar->getRepairedRoundTripRelations();
        $bar->reset();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetPolymorphicOneToMany()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->reset();
        $bar = m::mock(MetadataProviderNew::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $bar->reset();
        $classen = [TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getRelationHolder')->andReturn($holder);

        $expected = $foo->getRepairedRoundTripRelations();
        $foo->reset();
        $actual = $bar->getRepairedRoundTripRelations();
        $bar->reset();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetPolymorphicManyToMany()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->reset();
        $bar = m::mock(MetadataProviderNew::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $bar->reset();
        $classen = [TestMorphManyToManyTarget::class, TestMorphManyToManySource::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getRelationHolder')->andReturn($holder);

        $expected = $foo->getRepairedRoundTripRelations();
        $foo->reset();
        $actual = $bar->getRepairedRoundTripRelations();
        $bar->reset();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetPolymorphicDualSource()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->reset();
        $bar = m::mock(MetadataProviderNew::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $bar->reset();
        $classen = [TestMorphTargetAlternate::class, TestPolymorphicDualSource::class, TestMorphTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getRelationHolder')->andReturn($holder);

        $expected = $foo->getRepairedRoundTripRelations();
        $foo->reset();
        $actual = $bar->getRepairedRoundTripRelations();
        $bar->reset();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetPolymorphicMegamix()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->reset();
        $bar = m::mock(MetadataProviderNew::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $bar->reset();
        $classen = [TestMorphTargetAlternate::class, TestPolymorphicDualSource::class, TestMorphTarget::class,
            TestMorphOneSource::class, TestMorphOneSourceAlternate::class,
            TestMorphManySource::class, TestMorphManySourceAlternate::class,
            TestMorphManyToManyTarget::class, TestMorphManyToManySource::class,
        ];
        shuffle($classen);

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getCandidateModels')->andReturn($classen);
        $bar->shouldReceive('getRelationHolder')->andReturn($holder);

        $expected = $foo->getRepairedRoundTripRelations();
        $foo->reset();
        $actual = $bar->getRepairedRoundTripRelations();
        $bar->reset();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }
}
