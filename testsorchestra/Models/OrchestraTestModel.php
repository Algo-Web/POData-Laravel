<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 1:41 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;

class OrchestraTestModel extends \Illuminate\Database\Eloquent\Model
{
    use MetadataTrait;

    protected $table = 'test_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];
}
