<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Connection;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel as TestModel;
use Illuminate\Http\Request as Request;

class MetadataControllerTraitTest extends TestCase
{
    public function testGetMappings()
    {
        $foo = new TestController();
        $result = $foo->getMappings();

        $this->assertTrue(array_key_exists(TestModel::class, $result));
        $this->assertTrue(array_key_exists('create', $result[TestModel::class]));
        $this->assertTrue(array_key_exists('read', $result[TestModel::class]));
        $this->assertTrue(array_key_exists('update', $result[TestModel::class]));
        $this->assertTrue(array_key_exists('delete', $result[TestModel::class]));
        $this->assertEquals('storeTestModel', $result[TestModel::class]['create'][0]);
        $this->assertEquals('showTestModel', $result[TestModel::class]['read'][0]);
        $this->assertEquals('updateTestModel', $result[TestModel::class]['update'][0]);
        $this->assertEquals('destroyTestModel', $result[TestModel::class]['delete'][0]);
    }

    public function testGetMethodNameOnEmptyArray()
    {
        $foo = new TestController();
        $foo->setMapping([]);

        $expected = 'assert(): Mapping array must not be empty failed';
        $actual = null;
        try {
            $foo->getMethodName('', '');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMethodMissingModelName()
    {
        $foo = new TestController();

        $expected = 'Metadata mapping for model  not defined';
        $actual = null;
        try {
            $foo->getMethodName('', '');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMethodBadCrudVerb()
    {
        $foo = new TestController();

        $expected = 'CRUD verb remix not defined';
        $actual = null;
        try {
            $foo->getMethodName(TestModel::class, 'remix');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testModelMappingNotArray()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => '']);

        $expected = 'Metadata mapping for model AlgoWeb\PODataLaravel\Models\TestModel not an array';
        $actual = null;
        try {
            $foo->getMethodName(TestModel::class, 'delete');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testModelMappingVerbNotDefined()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => []]);

        $expected = 'Metadata mapping for CRUD verb delete on model AlgoWeb\PODataLaravel\Models\TestModel not defined';
        $actual = null;
        try {
            $foo->getMethodName(TestModel::class, 'delete');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testModelMappingVerbNull()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => ['delete' => null]]);

        $expected = 'Metadata mapping for CRUD verb delete on model AlgoWeb\PODataLaravel\Models\TestModel null';
        $actual = null;
        try {
            $foo->getMethodName(TestModel::class, 'delete');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testModelMappingVerbMethodWrong()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => ['delete' => 'MoshAroundTheWorld']]);

        $expected = 'Metadata target for CRUD verb delete on model';
        $expected .= ' AlgoWeb\PODataLaravel\Models\TestModel does not exist';
        $actual = null;
        try {
            $foo->getMethodName(TestModel::class, 'delete');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testModelMappingDelete()
    {
        $foo = new TestController();

        $result = $foo->getMethodName(TestModel::class, 'delete');
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals('destroyTestModel', $result[0]);
        $this->assertTrue(is_array($result[1]));
        $this->assertEquals(1, count($result[1]));
        $this->assertEquals('id', $result[1][0]['name']);
    }

    public function testModelMappingUpdate()
    {
        $foo = new TestController();

        $result = $foo->getMethodName(TestModel::class, 'update');
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
        $this->assertEquals('updateTestModel', $result[0]);
        $this->assertTrue(is_array($result[1]));
        $this->assertEquals(2, count($result[1]));
        $this->assertEquals('request', $result[1][0]['name']);
        $this->assertEquals('Illuminate\Http\Request', $result[1][0]['type']);
        $this->assertEquals('id', $result[1][1]['name']);
    }

    public function testGetMappingsMissingModelName()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => '']);

        $expected = 'Metadata mapping for model AlgoWeb\PODataLaravel\Models\TestModel not an array';
        $actual = null;
        try {
            $foo->getMappings();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMappingsBadCrudVerb()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => ['flatten' => 'toTheSoundOfTheDrums']]);

        $expected = 'CRUD verb flatten not defined';
        $actual = null;
        try {
            $foo->getMappings();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMappingsCrudMappingNull()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => ['delete' => null]]);

        $expected = 'Metadata mapping for CRUD verb delete on model AlgoWeb\PODataLaravel\Models\TestModel null';
        $actual = null;
        try {
            $foo->getMappings();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetMappingsNonExistentMethod()
    {
        $foo = new TestController();
        $foo->setMapping([TestModel::class => ['delete' => 'toTheSoundOfTheDrums']]);

        $expected = 'Metadata target for CRUD verb delete on model';
        $expected .= ' AlgoWeb\PODataLaravel\Models\TestModel does not exist';
        $actual = null;
        try {
            $foo->getMappings();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
