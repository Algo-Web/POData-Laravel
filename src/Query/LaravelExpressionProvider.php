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
    const EQUAL = '=';
    const GREATER_THAN = '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const LESS_THAN = '<';
    const LESS_THAN_OR_EQUAL = '<=';
    const LOGICAL_AND = '&&';
    const LOGICAL_NOT = '!';
    const LOGICAL_OR = '||';
    const MEMBER_ACCESS = '';
    const MODULO = '%';
    const MULTIPLY = '*';
    const NEGATE = '-';
    const NOT_EQUAL = '!=';
    const OPEN_BRACKET = '(';
    /**
     * The type of the resource pointed by the resource path segment.
     *
     * @var ResourceType
     */
    private $resourceType;
    private $entityMapping;
    private $QueryBuilder;
    /**
     * Constructs new instance of MySQLExpressionProvider.
     */
    public function __construct($QueryBuilder)
    {
        $this->QueryBuilder = $QueryBuilder;
        $this->entityMapping = array();
    }
    /**
     * Get the name of the iterator.
     *
     * @return string
     */
    public function getIteratorName()
    {
        return null;
    }
    /**
     * call-back for setting the resource type.
     *
     * @param ResourceType $resourceType The resource type on which the filter is going to be applied
     */
    public function setResourceType(ResourceType $resourceType)
    {
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
                throw new \InvalidArgumentException('onArithmeticExpression');
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
        $entityTypeName = $this->resourceType->getName();
        $propertyName = $parent->getResourceProperty()->getName();
        if (is_array($this->entityMapping)) {
            if (array_key_exists($entityTypeName, $this->entityMapping)) {
                if (array_key_exists($propertyName, $this->entityMapping[$entityTypeName])) {
                    return $this->entityMapping[$entityTypeName][$propertyName];
                }
            }
        }
        return $propertyName;
    }
    /**
     * Call-back for function call expression.
     *
     * @param FunctionDescription $functionDescription Description of the function
     * @param array<string>       $params              Paameters to the function
     *
     * @return string
     */
    public function onFunctionCallExpression($functionDescription, $params)
    {
        switch ($functionDescription->name) {
            case ODataConstants::STRFUN_COMPARE:
                $this->QueryBuilder->where($params[0], '=', $params[1]);
                return $this->getSql();
            case ODataConstants::STRFUN_ENDSWITH:
                return "(STRCMP($params[1],RIGHT($params[0],LENGTH($params[1]))) = 0)";
            case ODataConstants::STRFUN_INDEXOF:
                return "INSTR($params[0], $params[1]) - 1";
            case ODataConstants::STRFUN_REPLACE:
                return "REPLACE($params[0],$params[1],$params[2])";
            case ODataConstants::STRFUN_STARTSWITH:
                return "(STRCMP($params[1],LEFT($params[0],LENGTH($params[1]))) = 0)";
            case ODataConstants::STRFUN_TOLOWER:
                return "LOWER($params[0])";
            case ODataConstants::STRFUN_TOUPPER:
                return "UPPER($params[0])";
            case ODataConstants::STRFUN_TRIM:
                return "TRIM($params[0])";
            case ODataConstants::STRFUN_SUBSTRING:
                return count($params) == 3 ?
                    "SUBSTRING($params[0], $params[1] + 1, $params[2])" : "SUBSTRING($params[0], $params[1] + 1)";
            case ODataConstants::STRFUN_SUBSTRINGOF:
                return "(LOCATE($params[0], $params[1]) > 0)";
            case ODataConstants::STRFUN_CONCAT:
                return "CONCAT($params[0],$params[1])";
            case ODataConstants::STRFUN_LENGTH:
                return "LENGTH($params[0])";
            case ODataConstants::GUIDFUN_EQUAL:
                return "STRCMP($params[0], $params[1])";
            case ODataConstants::DATETIME_COMPARE:
                return "DATETIMECMP($params[0]; $params[1])";
            case ODataConstants::DATETIME_YEAR:
                return 'EXTRACT(YEAR from ' . $params[0] . ')';
            case ODataConstants::DATETIME_MONTH:
                return 'EXTRACT(MONTH from ' . $params[0] . ')';
            case ODataConstants::DATETIME_DAY:
                return 'EXTRACT(DAY from ' . $params[0] . ')';
            case ODataConstants::DATETIME_HOUR:
                return 'EXTRACT(HOUR from ' . $params[0] . ')';
            case ODataConstants::DATETIME_MINUTE:
                return 'EXTRACT(MINUTE from ' . $params[0] . ')';
            case ODataConstants::DATETIME_SECOND:
                return 'EXTRACT(SECOND from ' . $params[0] . ')';
            case ODataConstants::MATHFUN_ROUND:
                return "ROUND($params[0])";
            case ODataConstants::MATHFUN_CEILING:
                return "CEIL($params[0])";
            case ODataConstants::MATHFUN_FLOOR:
                return "FLOOR($params[0])";
            case ODataConstants::BINFUL_EQUAL:
                return "($params[0]  = $params[1])";
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
        //DATETIMECMP
        if (!substr_compare($left, 'DATETIMECMP', 0, 11)) {
            $str = explode(';', $left, 2);
            $str[0] = str_replace('DATETIMECMP', '', $str[0]);
            return self::OPEN_BRACKET
                .$str[0] . ' ' . $operator
                .' ' . $str[1] . self::CLOSE_BRACKET;
        }
        //return self::OPEN_BRACKET . $left . ' ' . $operator . ' ' . $right . self::CLOSE_BRACKET;
        return $this->getSql();
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
        return $operator . self::OPEN_BRACKET . $child . self::CLOSE_BRACKET;
    }

    private function getSql()
    {
        $sql = $this->replace($this->QueryBuilder->toSql(), $this->QueryBuilder->getBindings());

        $nuSql = str_replace("select * from \"dummy\" where", "", $sql);
        $nuSql = str_replace("select * from `dummy` where", "", $nuSql);
        $nuSql = str_replace("select * from \'dummy\' where", "", $nuSql);

        return $nuSql;
    }

   private function replace($sql, $bindings)
    {
        $needle = '?';
        foreach ($bindings as $replace) {
            $pos = strpos($sql, $needle);
            if ($pos !== false) {
                $sql = substr_replace($sql, $replace, $pos, strlen($needle));
            }
        }
        return $sql;
    }
}
