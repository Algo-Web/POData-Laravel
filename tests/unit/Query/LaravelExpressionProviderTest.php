<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider as LaravelExpressionProvider;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use POData\Common\ODataConstants;

/**
 * Generated Test Class.
 */
class LaravelExpressionProviderTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
//        $this->object = new \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::getIteratorName
     */
    public function testGetIteratorName()
    {
        $foo = new LaravelExpressionProvider();
        $this->assertTrue(null == $foo->getIteratorName());
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::setResourceType
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::getResourceType
     */
    public function testSetResourceType()
    {
        $resource = \Mockery::mock(ResourceType::class);
        $resource->shouldReceive('getName')->andReturn('dangerZone');
        $expected = '$dangerZone';

        $foo = new LaravelExpressionProvider();
        $foo->setResourceType($resource);
        $result = $foo->getIteratorName();
        $this->assertEquals($expected, $result);
        $remix = $foo->getResourceType();
        $this->assertEquals('dangerZone', $remix->getName());
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onLogicalExpression
     * @todo   Implement testOnLogicalExpression().
     */
    public function testOnLogicalExpression()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOnLogicalExpressionNullArguments()
    {
        $foo = new LaravelExpressionProvider();
        $expected = 'onLogicalExpression';
        $actual = null;

        try {
            $result = $foo->onLogicalExpression(null, null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testOnLogicalExpressionLogicalAnd()
    {
        $foo = new LaravelExpressionProvider();
        $left = 'x < 4';
        $right = 'y > 2';
        $type = ExpressionType::AND_LOGICAL;
        $expected = '(x < 4 && y > 2)';

        $result = $foo->onLogicalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnLogicalExpressionLogicalOr()
    {
        $foo = new LaravelExpressionProvider();
        $left = 'x < 4';
        $right = 'y > 2';
        $type = ExpressionType::OR_LOGICAL;
        $expected = '(x < 4 || y > 2)';

        $result = $foo->onLogicalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onArithmeticExpression
     * @todo   Implement testOnArithmeticExpression().
     */
    public function testOnArithmeticExpression()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOnArithmeticExpressionNullArguments()
    {
        $foo = new LaravelExpressionProvider();
        $expected = 'onArithmeticExpression';
        $actual = null;

        try {
            $result = $foo->onArithmeticExpression(null, null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testOnArithmeticExpressionMultiply()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::MULTIPLY;
        $expected = '(4 * 2)';

        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnArithmeticExpressionDivide()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::DIVIDE;
        $expected = '(4 / 2)';

        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnArithmeticExpressionAdd()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::ADD;
        $expected = '(4 + 2)';

        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnArithmeticExpressionSubtract()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::SUBTRACT;
        $expected = '(4 - 2)';

        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnArithmeticExpressionModulus()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::MODULO;
        $expected = '(4 % 2)';

        $result = $foo->onArithmeticExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onRelationalExpression
     * @todo   Implement testOnRelationalExpression().
     */
    public function testOnRelationalExpression()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOnRelationalExpressionNullArguments()
    {
        $foo = new LaravelExpressionProvider();
        $expected = 'onRelationalExpression';
        $actual = null;

        try {
            $result = $foo->onRelationalExpression(null, null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testOnRelationalExpressionGreaterThan()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::GREATERTHAN;
        $expected = '(4 > 2)';

        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnRelationalExpressionGreaterThanOrEqual()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::GREATERTHAN_OR_EQUAL;
        $expected = '(4 >= 2)';

        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnRelationalExpressionLesserThan()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::LESSTHAN;
        $expected = '(4 < 2)';

        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnRelationalExpressionLesserThanOrEqual()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::LESSTHAN_OR_EQUAL;
        $expected = '(4 <= 2)';

        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnRelationalExpressionEquals()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::EQUAL;
        $expected = '(4 == 2)';

        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    public function testOnRelationalExpressionNotEquals()
    {
        $foo = new LaravelExpressionProvider();
        $left = '4';
        $right = 2;
        $type = ExpressionType::NOTEQUAL;
        $expected = '(4 != 2)';

        $result = $foo->onRelationalExpression($type, $left, $right);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onUnaryExpression
     * @todo   Implement testOnUnaryExpression().
     */
    public function testOnUnaryExpression()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOnUnaryExpressionNullArguments()
    {
        $foo = new LaravelExpressionProvider();
        $expected = 'onUnaryExpression';
        $actual = null;

        try {
            $result = $foo->onUnaryExpression(null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testOnUnaryExpressionNegation()
    {
        $foo = new LaravelExpressionProvider();

        $type = ExpressionType::NEGATE;
        $child = 'hammertime';

        $expected = '-(hammertime)';

        $result = $foo->onUnaryExpression($type, $child);
        $this->assertEquals($expected, $result);
    }

    public function testOnUnaryExpressionLogicalNot()
    {
        $foo = new LaravelExpressionProvider();

        $type = ExpressionType::NOT_LOGICAL;
        $child = 'hammertime';

        $expected = '!(hammertime)';

        $result = $foo->onUnaryExpression($type, $child);
        $this->assertEquals($expected, $result);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onConstantExpression
     * @dataProvider onConstantExpressionProvider
     */
    public function testOnConstantExpression($type, $value, $expected)
    {
        $foo = new LaravelExpressionProvider();
        $this->assertEquals($expected, $foo->onConstantExpression($type, $value));
    }

    public function onConstantExpressionProvider()
    {
        return [
            [new \POData\Providers\Metadata\Type\Null1, null, "NULL"],
            [new \POData\Providers\Metadata\Type\Boolean , true, "true"],
            [new \POData\Providers\Metadata\Type\Boolean , false, "false"],
            [new \POData\Providers\Metadata\Type\Byte, 254, 254],
            [new \POData\Providers\Metadata\Type\Int16, 32767, 32767],
            [new \POData\Providers\Metadata\Type\Int32, 2147483647, 2147483647],
            [new \POData\Providers\Metadata\Type\Int64, 9223372036854775807, 9223372036854775807],
            [new \POData\Providers\Metadata\Type\StringType, "the mighty string", "the mighty string"],

        ];
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onPropertyAccessExpression
     * @todo   Implement testOnPropertyAccessExpression().
     */
    public function testOnPropertyAccessExpression()
    {
        $topLevelPropertyAccess = \Mockery::mock(
            'POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression'
        )->makePartial();
        $SecondLevelPropertyAccess = \Mockery::mock(
            'POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression'
        )->makePartial();
        $topLevelResourceProperty = \Mockery::mock('POData\Providers\Metadata\ResourceProperty')->makePartial();
        $secondLevelResourceProperty = \Mockery::mock('POData\Providers\Metadata\ResourceProperty')->makePartial();


        $topLevelPropertyAccess->shouldReceive('getParent')->andReturn($SecondLevelPropertyAccess);
        $topLevelPropertyAccess->shouldReceive('getResourceProperty')->andReturn($topLevelResourceProperty);
        $SecondLevelPropertyAccess->shouldReceive('getResourceProperty')->andReturn($secondLevelResourceProperty);
        $topLevelResourceProperty->shouldReceive('getName')->andReturn("TopPropertyAccessor");
        $secondLevelResourceProperty->shouldReceive('getName')->andReturn("SecondPropertyAccessor");


        $foo = new LaravelExpressionProvider();
        $fooRef= new \ReflectionObject($foo);
        $refProperty = $fooRef->getProperty('iteratorName');
        $refProperty->setAccessible(true);
        $refProperty->setValue($foo, 'testIterator');
        $expected = "testIterator->SecondPropertyAccessor->TopPropertyAccessor";
        $result = $foo->onPropertyAccessExpression($topLevelPropertyAccess);
        $this->assertEquals($expected, $result);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider::onFunctionCallExpression
     * @todo   Implement testOnFunctionCallExpression().
     */
    public function testOnFunctionCallExpression()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOnFunctionCallExpressionNullArguments()
    {
        $foo = new LaravelExpressionProvider();
        $expected = 'onFunctionCallExpression';
        $actual = null;

        try {
            $result = $foo->onFunctionCallExpression(null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testOnFunctionCallExpressionBadName()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription('ka-fricking-boom', null, null);

        $expected = 'onFunctionCallExpression';
        $actual = null;

        try {
            $result = $foo->onFunctionCallExpression($func, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testOnFunctionCallExpressionStrCmp()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_COMPARE, null, null);

        $expected = "strcmp(foo, bar)";
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrEndsWith()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_ENDSWITH, null, null);

        $expected = "(strcmp(substr(foo, strlen(foo) - strlen(bar)), bar) === 0)";
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrIndexOf()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_INDEXOF, null, null);

        $expected = "strpos(foo, bar)";
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrReplace()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_REPLACE, null, null);

        $expected = 'str_replace(bar, city, foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar', 'city']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrStartsWith()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_STARTSWITH, null, null);

        $expected = '(strpos(foo, bar) === 0)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrToLower()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_TOLOWER, null, null);

        $expected = 'strtolower(FoO)';
        $result = $foo->onFunctionCallExpression($func, [ 'FoO']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrToUpper()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_TOUPPER, null, null);

        $expected = 'strtoupper(FoO)';
        $result = $foo->onFunctionCallExpression($func, [ 'FoO']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrTrim()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_TRIM, null, null);

        $expected = 'trim(FoO)';
        $result = $foo->onFunctionCallExpression($func, [ 'FoO']);
        $this->assertEquals($expected, $result);
    }
    public function testOnFunctionCallExpressionStrSubstring()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_SUBSTRING, null, null);

        $expected = "substr(foo, 2)";
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 2]);
        $this->assertEquals($expected, $result);
    }
    
    public function testOnFunctionCallExpressionStrSubstring2()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_SUBSTRING, null, null);

        $expected = "substr(foo, 2, 3)";
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 2, 3]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrSubstringOf()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_SUBSTRINGOF, null, null);

        $expected = '(strpos(bar, foo) !== false)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrConcat()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_CONCAT, null, null);

        $expected = 'foo . bar';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar']);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionStrLength()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::STRFUN_LENGTH, null, null);

        $expected = 'strlen(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionGuidEquality()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::GUIDFUN_EQUAL, null, null);

        $expected = 'POData\Providers\Metadata\Type\Guid::guidEqual(foo, bar)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeCompare()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_COMPARE, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::dateTimeCmp(foo, bar)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeYear()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_YEAR, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::year(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeMonth()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_MONTH, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::month(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeDay()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_DAY, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::day(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeHour()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_HOUR, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::hour(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeMinute()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_MINUTE, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::minute(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionDateTimeSecond()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::DATETIME_SECOND, null, null);

        $expected = 'POData\Providers\Metadata\Type\DateTime::second(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionMathFunRound()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::MATHFUN_ROUND, null, null);

        $expected = 'round(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionMathFunFloor()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::MATHFUN_FLOOR, null, null);

        $expected = 'floor(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionMathFunCeiling()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::MATHFUN_CEILING, null, null);

        $expected = 'ceil(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionBinaryEqual()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription(ODataConstants::BINFUL_EQUAL, null, null);

        $expected = 'POData\Providers\Metadata\Type\Binary::binaryEqual(foo, bar)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo', 'bar' ]);
        $this->assertEquals($expected, $result);
    }

    public function testOnFunctionCallExpressionIsNull()
    {
        $foo = new LaravelExpressionProvider();

        $func = new FunctionDescription('is_null', null, null);

        $expected = 'is_null(foo)';
        $result = $foo->onFunctionCallExpression($func, [ 'foo' ]);
        $this->assertEquals($expected, $result);
    }
}
