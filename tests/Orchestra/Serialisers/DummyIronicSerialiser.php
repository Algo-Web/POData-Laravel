<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 28/02/20
 * Time: 12:13 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers;

use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;

class DummyIronicSerialiser extends IronicSerialiser
{
    public function getLightStack() : array
    {
        return parent::getLightStack();
    }

    public function loadStackIfEmpty() : void
    {
        parent::loadStackIfEmpty();
    }
}
