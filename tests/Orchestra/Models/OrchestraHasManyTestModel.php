<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 2:11 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrchestraHasManyTestModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_has_many_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function parent() : BelongsTo
    {
        return $this->belongsTo(OrchestraHasManyThroughTestModel::class, 'parent_id');
    }

    public function children() : HasMany
    {
        return $this->hasMany(OrchestraBelongsToTestModel::class, 'parent_id');
    }
}
