<?php

namespace AlgoWeb\PODataLaravel\Auth;

use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use Illuminate\Database\Eloquent\Model;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;

class NullAuthProvider implements AuthInterface
{
    public function canAuth(ActionVerb $verb, $modelname, Model $model = null)
    {
        return true;
    }
}
