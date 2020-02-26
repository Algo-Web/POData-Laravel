<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection as Connection;
use Illuminate\Database\Eloquent\Model as Model;

class TestMonomorphicParentOfMorphTarget extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
        getRelationshipsFromMethods as getRel;
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
            $this->grammar = \Mockery::mock(\Illuminate\Database\Query\Grammars\Grammar::class)->makePartial();
            $connect = \Mockery::mock(Connection::class)->makePartial();
            $connect->shouldReceive('getQueryGrammar')->andReturn($this->grammar);
            $connect->shouldReceive('getPostProcessor')->andReturn($this->processor);
            $this->connect = $connect;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testmonomorphicparentofmorphtarget';
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

    public function getRelationshipsFromMethods($biDir = false)
    {
        return $this->getRel($biDir);
    }

    public function morphTargets()
    {
        return $this->hasMany(TestMorphTarget::class, 'child_id');
    }

    public function monomorphicChildren()
    {
        return $this->hasManyThrough(
            TestMonomorphicChildOfMorphTarget::class,
            TestMorphTarget::class,
            'id',
            'id',
            'parent_id',
            'child_id'
        );
        // localKey  on this                   ->   firstKey on TestMorphTarget
        // firstKey  on TestMorphTarget        ->   secondLocalKey on TestMorphTarget
        // secondLocalKey on TestMorphTarget   ->   secondKey on TestMonomorphicParentOfMorphTarget
    }
}
