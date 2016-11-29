<?php

namespace AlgoWeb\PODataLaravel\Interfaces;

use Illuminate\Database\Eloquent\Model;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;

interface AuthInterface
{

    /**
     * Is the requester permitted to perform the requested action on the model class (and instance, if supplied)?
     *
     * @param ActionVerb $verb
     * @param $modelname  Eloquent model class to access
     * @param Model|null $model  Specific eloquent model to access
     * @return bool
     */
    public function canAuth(ActionVerb $verb, $modelname, Model $model = null);
}
