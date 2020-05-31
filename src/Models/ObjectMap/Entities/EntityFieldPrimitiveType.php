<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

use MyCLabs\Enum\Enum;

/**
 * Class EntityFieldType.
 *
 * @method static EntityFieldPrimitiveType TARRAY()
 * @method static EntityFieldPrimitiveType SIMPLE_ARRAY()
 * @method static EntityFieldPrimitiveType JSON_ARRAY()
 * @method static EntityFieldPrimitiveType JSON()
 * @method static EntityFieldPrimitiveType BIGINT()
 * @method static EntityFieldPrimitiveType BOOLEAN()
 * @method static EntityFieldPrimitiveType DATETIME()
 * @method static EntityFieldPrimitiveType DATETIMETZ()
 * @method static EntityFieldPrimitiveType TIMESTAMP()
 * @method static EntityFieldPrimitiveType DATE()
 * @method static EntityFieldPrimitiveType TIME()
 * @method static EntityFieldPrimitiveType DECIMAL()
 * @method static EntityFieldPrimitiveType INTEGER()
 * @method static EntityFieldPrimitiveType OBJECT()
 * @method static EntityFieldPrimitiveType SMALLINT()
 * @method static EntityFieldPrimitiveType STRING()
 * @method static EntityFieldPrimitiveType TEXT()
 * @method static EntityFieldPrimitiveType BINARY()
 * @method static EntityFieldPrimitiveType BLOB()
 * @method static EntityFieldPrimitiveType FLOAT()
 * @method static EntityFieldPrimitiveType GUID()
 * @method static EntityFieldPrimitiveType DATEINTERVAL()
 */
class EntityFieldPrimitiveType extends Enum
{
    const TARRAY       = 'array';
    const SIMPLE_ARRAY = 'simple_array';
    const JSON_ARRAY   = 'json_array';
    const JSON         = 'json';
    const BIGINT       = 'bigint';
    const BOOLEAN      = 'boolean';
    const DATETIME     = 'datetime';
    const DATETIMETZ   = 'datetimetz';
    const TIMESTAMP    = 'timestamp';
    const DATE         = 'date';
    const TIME         = 'time';
    const DECIMAL      = 'decimal';
    const INTEGER      = 'integer';
    const OBJECT       = 'object';
    const SMALLINT     = 'smallint';
    const STRING       = 'string';
    const TEXT         = 'text';
    const BINARY       = 'binary';
    const BLOB         = 'blob';
    const FLOAT        = 'float';
    const GUID         = 'guid';
    const DATEINTERVAL = 'dateinterval';
}
