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
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrchestraMorphToTestModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_morph_to_target_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];

    public function parent(): MorphTo
    {
        return $this->morphTo('morph');
    }
}
