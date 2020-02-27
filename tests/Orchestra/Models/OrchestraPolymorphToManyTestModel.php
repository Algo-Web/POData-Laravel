<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26/02/20
 * Time: 3:06 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;

class OrchestraPolymorphToManyTestModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_polymorph_to_many_target_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function sourceParents()
    {
        return $this->morphedByMany(
            OrchestraPolymorphToManySourceModel::class,
            'manyable',
            'test_manyables',
            'many_id',
            'manyable_id'
        );
    }
}
