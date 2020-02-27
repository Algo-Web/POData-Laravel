<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27/02/20
 * Time: 11:38 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrchestraMorphOneTestModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_morph_one_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function child(): MorphOne
    {
        return $this->morphOne(OrchestraMorphToTestModel::class, 'morph');
    }
}
