<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;

class NullAuthProviderTest extends TestCase
{
    public function testCanAuth()
    {
        $foo = new NullAuthProvider();
        $result = $foo->canAuth(ActionVerb::CREATE(), 'model');
        $this->assertTrue($result);
    }
}
