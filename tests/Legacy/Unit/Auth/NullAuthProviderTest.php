<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Auth;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

class NullAuthProviderTest extends TestCase
{
    public function testCanAuth()
    {
        $foo    = new NullAuthProvider();
        $result = $foo->canAuth(ActionVerb::CREATE(), 'model');
        $this->assertTrue($result);
    }
}
