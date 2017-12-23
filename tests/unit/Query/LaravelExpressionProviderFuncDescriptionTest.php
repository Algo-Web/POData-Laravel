<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider as LaravelExpressionProvider;
use POData\Common\ODataConstants;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use Mockery as m;

class LaravelExpressionProviderFuncDescriptionTest extends TestCase
{
    public function testStartsWithEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_STARTSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'string', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testStartsWithEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_STARTSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'string', "''"]);
        $this->assertEquals($expected, $actual);
    }

    public function testStartsWithNullString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_STARTSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'string', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testEndsWithEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_ENDSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'string', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testEndsOfEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_ENDSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);
    }

    public function testCompareWithEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_COMPARE;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'strng', '']);
        $this->assertEquals($expected, $actual);
    }
}
