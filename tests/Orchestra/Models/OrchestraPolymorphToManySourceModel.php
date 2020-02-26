<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26/02/20
 * Time: 3:06 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;

class OrchestraPolymorphToManySourceModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_polymorph_to_many_source_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function sourceChildren()
    {
        return $this->morphToMany(
            OrchestraPolymorphToManyTestModel::class,
            'manyable',
            'test_manyables',
            'manyable_id',
            'many_id'
        );
    }
}
