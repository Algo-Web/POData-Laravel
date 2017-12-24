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

    public function testStartsOfEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_STARTSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'string']);
        $this->assertEquals($expected, $actual);
    }

    public function testStartsOfEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_STARTSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ "''", 'string']);
        $this->assertEquals($expected, $actual);
    }

    public function testStartsOfNullString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_STARTSWITH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'string']);
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

    public function testEmptyStringIsNull()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = 'is_null';

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }

    public function testIndexOfEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_INDEXOF;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);
    }

    public function testIndexByEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_INDEXOF;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'strng', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testSubstringOfEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_SUBSTRINGOF;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);
    }

    public function testSubstringByEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_SUBSTRINGOF;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'strng', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testRoundEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::MATHFUN_ROUND;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '']);
        $this->assertEquals($expected, $actual);
    }

    public function testCeilEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::MATHFUN_CEILING;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '']);
        $this->assertEquals($expected, $actual);
    }

    public function testFloorEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::MATHFUN_FLOOR;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '']);
        $this->assertEquals($expected, $actual);
    }

    public function testToLowerEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_TOLOWER;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '']);
        $this->assertEquals($expected, $actual);
    }

    public function testToUpperEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_TOUPPER;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '']);
        $this->assertEquals($expected, $actual);
    }

    public function testTrimEmptyString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_TRIM;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '']);
        $this->assertEquals($expected, $actual);
    }

    public function testConcatEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_CONCAT;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'strng', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testConcatToEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_CONCAT;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);
    }

    public function testLengthOfEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_LENGTH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);
    }

    public function testSubstringEmptyQuotedString()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::STRFUN_SUBSTRING;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'string', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeCompareEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_COMPARE;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '', 'strng']);
        $this->assertEquals($expected, $actual);

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'string', '']);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeCompareEmptyQuotedStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_COMPARE;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ 'strng', "''"]);
        $this->assertEquals($expected, $actual);

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ "''", 'strng']);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeYearEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_YEAR;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeMonthEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_MONTH;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeDayEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_DAY;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeHourEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_HOUR;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeMinuteEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_MINUTE;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }

    public function testDateTimeSecondEmptyStrings()
    {
        $foo = new LaravelExpressionProvider();

        $function = m::mock(FunctionDescription::class);
        $function->name = ODataConstants::DATETIME_SECOND;

        $expected = 'true';
        $actual = $foo->onFunctionCallExpression($function, [ '' ]);
        $this->assertEquals($expected, $actual);
    }
}
