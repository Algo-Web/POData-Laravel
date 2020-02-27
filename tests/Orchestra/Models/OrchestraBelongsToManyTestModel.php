<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 1:41 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrchestraBelongsToManyTestModel extends \Illuminate\Database\Eloquent\Model
{
    use MetadataTrait;

    protected $table = 'test_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            OrchestraBelongsToManyTestModel::class,
            'test_belongs_to_many_pivot',
            'left_id',
            'right_id'
        );
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            OrchestraBelongsToManyTestModel::class,
            'test_belongs_to_many_pivot',
            'right_id',
            'left_id'
        );
    }
}
