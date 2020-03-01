<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/03/20
 * Time: 1:32 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;

class OrchestraPolymorphToManySourceMalformedModel extends Model
{
    use MetadataTrait;

    protected $table = 'test_polymorph_to_many_source_models';

    protected $fillable = [ 'name', 'added_at', 'weight', 'code'];
    // These methods are deliberately NOT PSR-2 compliant in order to give mutants in ModelReflectionHelper something to trip over
    public function sourceChildren() { return $this->morphToMany(OrchestraPolymorphToManyTestModel::class, 'manyable', 'test_manyables', 'manyable_id', 'many_id'); }
    public function child() { return $this->morphMany(OrchestraMorphToTestModel::class, 'morph');
    }
    public function voodooChild() {
        return (function(){
            return $this->morphMany(OrchestraMorphToTestModel::class, 'morph');
})();

    }
}
