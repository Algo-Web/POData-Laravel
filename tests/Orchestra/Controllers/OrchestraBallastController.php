<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 3:50 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers;

use AlgoWeb\PODataLaravel\Controllers\Controller;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerTrait;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\RelationTestDummyModel;

class OrchestraBallastController extends Controller
{
    use MetadataControllerTrait;

    public function __construct()
    {
        $this->mapping = [
            RelationTestDummyModel::class =>
                [
                    'create' => 'storeTestModel',
                    'read' => 'showTestModel',
                    'update' => 'updateTestModel',
                    'delete' => 'destroyTestModel'
                ]
        ];
    }

    public function storeTestModel()
    {
    }

    public function showTestModel()
    {
    }

    public function updateTestModel()
    {
    }

    public function destroyTestModel()
    {
    }
}
