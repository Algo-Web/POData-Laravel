<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Mockery as m;

class MetadataRelationHolderCharacterisationTest extends TestCase
{
    public function testGetSingleMonomorphicRelationPair()
    {
        $holder = new MetadataRelationHolder();

        $src = new TestMonomorphicOneAndManySource();
        $targ = new TestMonomorphicOneAndManyTarget();

        $holder->addModel($src);
        $holder->addModel($targ);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $classen = [TestMonomorphicOneAndManySource::class, TestMonomorphicOneAndManyTarget::class];
        foreach ($classen as $className) {
            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetMonomorphicOneToOneAndOneToMany()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetMonomorphicManyToMany()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $classen = [TestMonomorphicManySource::class, TestMonomorphicManyTarget::class];

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }

    public function testGetPolymorphicOneToOne()
    {
        $holder = new MetadataRelationHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $classen = [TestMorphOneSource::class, TestMorphOneSourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
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
        $classen = [TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
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
        $classen = [TestMorphManyToManySource::class, TestMorphManyToManyTarget::class];

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
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
        $classen = [TestPolymorphicDualSource::class, TestMorphTarget::class, TestMorphTargetAlternate::class];

        foreach ($classen as $className) {
            $checkModel = new $className();
            $holder->addModel($checkModel);

            $testModel = m::mock($className)->makePartial();
            //$testModel->shouldReceive('getXmlSchema')->andReturn(null);
            $testModel->shouldReceive('metadata')->andReturn([]);
            App::instance($className, $testModel);
        }

        $foo->shouldReceive('getCandidateModels')->andReturn($classen);

        $expected = $foo->calculateRoundTripRelations();
        $actual = $holder->getRelations();
        $this->assertEquals(count($expected), count($actual));
        $numRows = count($expected);
        for ($i = 0; $i < $numRows; $i++) {
            $this->assertTrue(in_array($expected[$i], $actual), $i);
            $this->assertTrue(in_array($actual[$i], $expected), $i);
        }
    }
}
