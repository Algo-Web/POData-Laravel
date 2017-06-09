<?php

namespace AlgoWeb\PODataLaravel\Interfaces;

use Illuminate\Database\Eloquent\Model;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use Illuminate\Database\Eloquent\Relations\Relation;

interface AuthInterface
{

    /**
     * Is the requester permitted to perform the requested action on the model class (and instance, if supplied)?
     *
     * @param ActionVerb $verb
     * @param $modelname  Model class to access
     * @param Model|Relation|null $model  Specific eloquent model to access
     * @return bool
     */
    public function canAuth(ActionVerb $verb, $modelname, $model = null);
}
