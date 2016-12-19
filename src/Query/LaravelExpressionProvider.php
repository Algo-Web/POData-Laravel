<?php

namespace AlgoWeb\PODataLaravel\Query;

use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\Providers\Metadata\Type\IType;
use POData\Common\ODataConstants;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use POData\Providers\Expression\IExpressionProvider;

class LaravelExpressionProvider implements IExpressionProvider
{
    const ADD = '+';
    const CLOSE_BRACKET = ')';
    const COMMA = ',';
    const DIVIDE = '/';
    const SUBTRACT = '-';
    const EQUAL = '==';
    const GREATER_THAN = '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const LESS_THAN = '<';
    const LESS_THAN_OR_EQUAL = '<=';
    const LOGICAL_AND = '&&';
    const LOGICAL_NOT = '!';
    const LOGICAL_OR = '||';
    const MEMBER_ACCESS = '->';
    const MODULO = '%';
    const MULTIPLY = '*';
    const NEGATE = '-';
    const NOT_EQUAL = '!=';
    const OPEN_BRACKET = '(';
    const TYPE_NAMESPACE = 'POData\\Providers\\Metadata\\Type\\';
    /**
     * The name of iterator.
     *
     * @var string
     */
    private $iteratorName;
    /**
     * The type of the resource pointed by the resource path segment.
     *
     * @var ResourceType
     */
    private $resourceType;
    /**
     */
    public function __construct()
    {
    }
    /**
     * Get the name of the iterator.
     *
     * @return string
     */
    public function getIteratorName()
    {
        return $this->iteratorName;
    }
    /**
     * call-back for setting the resource type.
     *
     * @param ResourceType $resourceType The resource type on which the filter
     *                                   is going to be applied
     */
    public function setResourceType(ResourceType $resourceType)
    {
        $this->iteratorName = "$".$resourceType->getName();
        $this->resourceType = $resourceType;
    }
    /**
     * Call-back for logical expression.
     *
     * @param ExpressionType $expressionType The type of logical expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     *
     * @return string
     */
    public function onLogicalExpression($expressionType, $left, $right)
    {
        switch ($expressionType) {
            case ExpressionType::AND_LOGICAL:
                return $this->_prepareBinaryExpression(self::LOGICAL_AND, $left, $right);
            case ExpressionType::OR_LOGICAL:
                return $this->_prepareBinaryExpression(self::LOGICAL_OR, $left, $right);
            default:
                throw new \InvalidArgumentException('onLogicalExpression');
        }
    }
    /**
     * Call-back for arithmetic expression.
     *
     * @param ExpressionType $expressionType The type of arithmetic expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     *
     * @return string
     */
    public function onArithmeticExpression($expressionType, $left, $right)
    {
        switch ($expressionType) {
            case ExpressionType::MULTIPLY:
                return $this->_prepareBinaryExpression(self::MULTIPLY, $left, $right);
            case ExpressionType::DIVIDE:
                return $this->_prepareBinaryExpression(self::DIVIDE, $left, $right);
            case ExpressionType::MODULO:
                return $this->_prepareBinaryExpression(self::MODULO, $left, $right);
            case ExpressionType::ADD:
                return $this->_prepareBinaryExpression(self::ADD, $left, $right);
            case ExpressionType::SUBTRACT:
                return $this->_prepareBinaryExpression(self::SUBTRACT, $left, $right);
            default:
                throw new \InvalidArgumentException('onArithmeticExpression');
        }
    }
    /**
     * Call-back for relational expression.
     *
     * @param ExpressionType $expressionType The type of relation expression
     * @param string         $left           The left expression
     * @param string         $right          The left expression
     *
     * @return string
     */
    public function onRelationalExpression($expressionType, $left, $right)
    {
        switch ($expressionType) {
            case ExpressionType::GREATERTHAN:
                return $this->_prepareBinaryExpression(self::GREATER_THAN, $left, $right);
            case ExpressionType::GREATERTHAN_OR_EQUAL:
                return $this->_prepareBinaryExpression(self::GREATER_THAN_OR_EQUAL, $left, $right);
            case ExpressionType::LESSTHAN:
                return $this->_prepareBinaryExpression(self::LESS_THAN, $left, $right);
            case ExpressionType::LESSTHAN_OR_EQUAL:
                return $this->_prepareBinaryExpression(self::LESS_THAN_OR_EQUAL, $left, $right);
            case ExpressionType::EQUAL:
                return $this->_prepareBinaryExpression(self::EQUAL, $left, $right);
            case ExpressionType::NOTEQUAL:
                return $this->_prepareBinaryExpression(self::NOT_EQUAL, $left, $right);
            default:
                throw new \InvalidArgumentException('onRelationalExpression');
        }
    }
    /**
     * Call-back for unary expression.
     *
     * @param ExpressionType $expressionType The type of unary expression
     * @param string         $child          The child expression
     *
     * @return string
     */
    public function onUnaryExpression($expressionType, $child)
    {
        switch ($expressionType) {
            case ExpressionType::NEGATE:
                return $this->_prepareUnaryExpression(self::NEGATE, $child);
            case ExpressionType::NOT_LOGICAL:
                return $this->_prepareUnaryExpression(self::LOGICAL_NOT, $child);
            default:
                throw new \InvalidArgumentException('onUnaryExpression');
        }
    }
    /**
     * Call-back for constant expression.
     *
     * @param IType $type  The type of constant
     * @param mixed $value The value of the constant
     *
     * @return string
     */
    public function onConstantExpression(IType $type, $value)
    {
        if (is_bool($value)) {
            return var_export($value, true);
        } elseif (is_null($value)) {
            return var_export(null, true);
        }
        return $value;
    }
    /**
     * Call-back for property access expression.
     *
     * @param PropertyAccessExpression $expression The property access expression
     *
     * @return string
     */
    public function onPropertyAccessExpression($expression)
    {
        $parent = $expression;
        $variable = null;
        do {
            $variable = $parent->getResourceProperty()->getName().self::MEMBER_ACCESS.$variable;
            $parent = $parent->getParent();
        } while ($parent != null);
        $variable = rtrim($variable, self::MEMBER_ACCESS);
        $variable = $this->getIteratorName().self::MEMBER_ACCESS.$variable;
        return $variable;
    }
    /**
     * Call-back for function call expression.
     *
     * @param \POData\UriProcessor\QueryProcessor\FunctionDescription $functionDescription Description of the function
     * @param array<string>                                           $params              Parameters to the function
     *
     * @return string
     */
    public function onFunctionCallExpression($functionDescription, $params)
    {
        if (!isset($functionDescription)) {
            throw new \InvalidArgumentException('onFunctionCallExpression');
        }
        switch ($functionDescription->name) {
            case ODataConstants::STRFUN_COMPARE:
                return "strcmp($params[0], $params[1])";
            case ODataConstants::STRFUN_ENDSWITH:
                return "(strcmp(substr($params[0], strlen($params[0]) - strlen($params[1])), $params[1]) === 0)";
            case ODataConstants::STRFUN_INDEXOF:
                return "strpos($params[0], $params[1])";
            case ODataConstants::STRFUN_REPLACE:
                return "str_replace($params[1], $params[2], $params[0])";
            case ODataConstants::STRFUN_STARTSWITH:
                return "(strpos($params[0], $params[1]) === 0)";
            case ODataConstants::STRFUN_TOLOWER:
                return "strtolower($params[0])";
            case ODataConstants::STRFUN_TOUPPER:
                return "strtoupper($params[0])";
            case ODataConstants::STRFUN_TRIM:
                return "trim($params[0])";
            case ODataConstants::STRFUN_SUBSTRING:
                return count($params) == 3 ?
                    "substr($params[0], $params[1], $params[2])" : "substr($params[0], $params[1])";
            case ODataConstants::STRFUN_SUBSTRINGOF:
                return "(strpos($params[1], $params[0]) !== false)";
            case ODataConstants::STRFUN_CONCAT:
                return $params[0].' . '.$params[1];
            case ODataConstants::STRFUN_LENGTH:
                return "strlen($params[0])";
            case ODataConstants::GUIDFUN_EQUAL:
                return self::TYPE_NAMESPACE."Guid::guidEqual($params[0], $params[1])";
            case ODataConstants::DATETIME_COMPARE:
                return self::TYPE_NAMESPACE."DateTime::dateTimeCmp($params[0], $params[1])";
            case ODataConstants::DATETIME_YEAR:
                return self::TYPE_NAMESPACE."DateTime::year($params[0])";
            case ODataConstants::DATETIME_MONTH:
                return self::TYPE_NAMESPACE."DateTime::month($params[0])";
            case ODataConstants::DATETIME_DAY:
                return self::TYPE_NAMESPACE."DateTime::day($params[0])";
            case ODataConstants::DATETIME_HOUR:
                return self::TYPE_NAMESPACE."DateTime::hour($params[0])";
            case ODataConstants::DATETIME_MINUTE:
                return self::TYPE_NAMESPACE."DateTime::minute($params[0])";
            case ODataConstants::DATETIME_SECOND:
                return self::TYPE_NAMESPACE."DateTime::second($params[0])";
            case ODataConstants::MATHFUN_ROUND:
                return "round($params[0])";
            case ODataConstants::MATHFUN_CEILING:
                return "ceil($params[0])";
            case ODataConstants::MATHFUN_FLOOR:
                return "floor($params[0])";
            case ODataConstants::BINFUL_EQUAL:
                return self::TYPE_NAMESPACE."Binary::binaryEqual($params[0], $params[1])";
            case 'is_null':
                return "is_null($params[0])";
            default:
                throw new \InvalidArgumentException('onFunctionCallExpression');
        }
    }
    /**
     * To format binary expression.
     *
     * @param string $operator The binary operator
     * @param string $left     The left operand
     * @param string $right    The right operand
     *
     * @return string
     */
    private function _prepareBinaryExpression($operator, $left, $right)
    {
        return
            self::OPEN_BRACKET.$left.' '.$operator.' '.$right.self::CLOSE_BRACKET;
    }
    /**
     * To format unary expression.
     *
     * @param string $operator The unary operator
     * @param string $child    The operand
     *
     * @return string
     */
    private function _prepareUnaryExpression($operator, $child)
    {
        return $operator.self::OPEN_BRACKET.$child.self::CLOSE_BRACKET;
    }
}
