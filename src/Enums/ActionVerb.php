<?php

namespace AlgoWeb\PODataLaravel\Enums;

use MyCLabs\Enum\Enum;

/**
 * @method static ActionVerb READ()
 * @method static ActionVerb CREATE()
 * @method static ActionVerb UPDATE()
 * @method static ActionVerb DELETE()
 */
class ActionVerb extends Enum
{
    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';
}
