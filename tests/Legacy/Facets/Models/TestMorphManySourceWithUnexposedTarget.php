<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Mockery as m;

class TestMorphManySourceWithUnexposedTarget extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
    }

    protected $metaArray;
    protected $connect;
    protected $grammar;
    protected $processor;

    protected $morphRelation;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['*'];

    public $primaryKey = 'alternate_id';

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
            assert(null !== $this->connect);
            assert(null !== $this->connect->getQueryGrammar());
            assert(null !== $this->connect->getPostProcessor());
        }
        parent::__construct();
        $morph   = m::mock(MorphMany::class)->makePartial();
        $related = m::mock(TestMorphUnexposedTarget::class)->makePartial();
        $morph->shouldReceive('getRelated')->andReturn($related);
        $this->morphRelation = $morph;
    }

    public function getTable()
    {
        return 'testmorphmanysourcewithunexposedtarget';
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
        return $this->morphRelation;
    }
}
