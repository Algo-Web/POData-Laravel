<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection as Connection;
use Illuminate\Database\Eloquent\Model as Model;

class TestMonomorphicChildOfMorphTarget extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
    }
    protected $metaArray;
    protected $connect;
    protected $grammar;
    protected $processor;

    public function __construct(array $meta = null, Connection $connect = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        if (isset($connect)) {
            $this->connect = $connect;
        } else {
            $this->processor = \Mockery::mock(\Illuminate\Database\Query\Processors\Processor::class)->makePartial();
            $this->grammar   = \Mockery::mock(\Illuminate\Database\Query\Grammars\Grammar::class)->makePartial();
            $connect         = \Mockery::mock(Connection::class)->makePartial();
            $connect->shouldReceive('getQueryGrammar')->andReturn($this->grammar);
            $connect->shouldReceive('getPostProcessor')->andReturn($this->processor);
            $this->connect = $connect;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testmonomorphicchildofmorphtarget';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    public function getConnection()
    {
        return $this->connect;
    }

    public function metadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }

    public function morphTarget()
    {
        return $this->belongsTo(TestMorphTarget::class, 'parent_id');
    }

    public function monomorphicParent()
    {
        return $this->hasManyThrough(
            TestMonomorphicParentOfMorphTarget::class,
            TestMorphTarget::class,
            'child_id', // key on TestMorphTarget table that links to local key on us
            'parent_id',   // Key on the TestMonomorphicParentOfMorphTarget table that links to TestMorphTarget
            'id', // Key on this that links to first key on TestMorphTarget
            'id' // key on TestMorphTarget that links to second key on TestMorphTarget
             // localKey  on this                   ->   firstKey on TestMorphTarget
             // firstKey  on TestMorphTarget        ->   secondLocalKey on TestMorphTarget
             // secondLocalKey on TestMorphTarget   ->   secondKey on TestMonomorphicParentOfMorphTarget
        );
    }
}
