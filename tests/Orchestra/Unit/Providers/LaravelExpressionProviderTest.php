<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 5/03/20
 * Time: 12:06 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Providers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;

class LaravelExpressionProviderTest extends TestCase
{
    public function testOnLogicalExpressionWithNonLogicalExpression()
    {
        $foo = new LaravelExpressionProvider();

        $type = ExpressionType::MULTIPLY();

        $this->expectException(\InvalidArgumentException::class);

        $foo->onLogicalExpression($type, '', '');
    }

    public function testOnArithmeticExpressionWithNonArithmeticExpression()
    {
        $foo = new LaravelExpressionProvider();

        $type = ExpressionType::AND_LOGICAL();

        $this->expectException(\InvalidArgumentException::class);

        $foo->onArithmeticExpression($type, '', '');
    }

    public function testOnRelationalExpressionWithNonRelationalExpression()
    {
        $foo = new LaravelExpressionProvider();

        $type = ExpressionType::AND_LOGICAL();

        $this->expectException(\InvalidArgumentException::class);

        $foo->onRelationalExpression($type, '', '');
    }

    public function testOnUnaryExpressionWithNonUnaryExpression()
    {
        $foo = new LaravelExpressionProvider();

        $type = ExpressionType::MULTIPLY();

        $this->expectException(\InvalidArgumentException::class);

        $foo->onUnaryExpression($type, '');
    }
}
