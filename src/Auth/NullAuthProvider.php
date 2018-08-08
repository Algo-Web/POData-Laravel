<?php

namespace AlgoWeb\PODataLaravel\Auth;

use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class NullAuthProvider implements AuthInterface
{
    /**
     * @param ActionVerb|AlgoWeb\PODataLaravel\Enums\ActionVerb                                                                                                                                                              $verb
     * @param string                                                                                                                                                                                                         $modelname
     * @param AlgoWeb\PODataLaravel\Models\TestModel|Mockery_21_AlgoWeb_PODataLaravel_Models_TestModel|Mockery_68_AlgoWeb_PODataLaravel_Models_TestMorphManySource|Mockery_75_Illuminate_Database_Eloquent_Relations_HasMany $model
     *
     * @return bool
     */
    public function canAuth(ActionVerb $verb, $modelname, $model = null)
    {
        return true;
    }
}
