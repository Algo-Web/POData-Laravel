<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/03/20
 * Time: 12:05 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models\ObjectMap\Entities;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;

class DummyEntityGubbins extends EntityGubbins
{
    public function getFieldNames() : array
    {
        return parent::getFieldNames();
    }

    public function getAssociationNames() : array
    {
        return parent::getAssociationNames();
    }
}
