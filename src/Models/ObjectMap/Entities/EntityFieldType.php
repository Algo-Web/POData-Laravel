<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

use MyCLabs\Enum\Enum;

/**
 * Class EntityFieldType.
 *
 * @method static EntityFieldType PRIMITIVE()
 * @method static EntityFieldType PRIMITIVE_BAG()
 */
class EntityFieldType extends Enum
{
    const PRIMITIVE = 1;
    const PRIMITIVE_BAG = 2;
}
