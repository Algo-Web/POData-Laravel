<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 5:18 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;

class MetadataGubbinsHolderTest extends TestCase
{
    public function testGetRelationsByClass()
    {
        $foo = new MetadataGubbinsHolder();

        $this->expectExceptionMessage('AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel does not exist in holder');

        $foo->getRelationsByClass(OrchestraTestModel::class);
    }
}
