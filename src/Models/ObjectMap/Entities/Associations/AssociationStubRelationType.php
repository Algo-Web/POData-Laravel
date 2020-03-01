<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use MyCLabs\Enum\Enum;

/**
 * Class AssociationStubRelationType.
 *
 * @method static AssociationStubRelationType NULL_ONE()
 * @method static AssociationStubRelationType ONE()
 * @method static AssociationStubRelationType MANY()
 */
class AssociationStubRelationType extends Enum
{
    const NULL_ONE = 1;
    const ONE      = 2;
    const MANY     = 4;
}
