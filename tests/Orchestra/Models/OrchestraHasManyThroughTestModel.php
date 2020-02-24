<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 2:12 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class OrchestraHasManyThroughTestModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_has_many_through_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function children() : HasMany
    {
        return $this->hasMany(OrchestraHasManyTestModel::class, 'parent_id');
    }

    public function grandchildren() : HasManyThrough
    {
        return $this->hasManyThrough(
            OrchestraBelongsToTestModel::class,
            OrchestraHasManyTestModel::class,
            'parent_id',
            'parent_id'
        );
    }
}
