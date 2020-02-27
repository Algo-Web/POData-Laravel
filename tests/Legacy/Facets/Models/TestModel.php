<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerTrait;
use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection as Connection;
use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;

class TestModel extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
        isRunningInArtisan as isArtisan;
    }
    use MetadataControllerTrait;

    protected $metaArray;
    protected $connect;
    protected $grammar;
    protected $processor;
    protected $isArtisan;

    public function __construct(array $meta = null, $endpoint = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        if (isset($endpoint)) {
            $this->endpoint = $endpoint;
        }
        parent::__construct();
        $this->processor = \Mockery::mock(\Illuminate\Database\Query\Processors\Processor::class)->makePartial();
        $this->grammar   = \Mockery::mock(\Illuminate\Database\Query\Grammars\Grammar::class)->makePartial();
        $connect         = \Mockery::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getQueryGrammar')->andReturn($this->grammar);
        $connect->shouldReceive('getPostProcessor')->andReturn($this->processor);
        $this->connect = $connect;
        $builder       = new Builder($this->connect, $this->grammar, $this->processor);
        $this->setQuery($builder);
        $this->dateFormat = 'Y-m-d H:i:s.u';
    }

    public function getTable()
    {
        return 'testmodel';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    public function getConnection()
    {
        return $this->connect;
    }

    protected function getAllAttributes()
    {
        return ['id' => 0, 'name' => '', 'added_at' => '', 'weight' => '', 'code' => ''];
    }

    public function getFillable()
    {
        return [ 'name', 'added_at', 'weight', 'code'];
    }

    public static function findOrFail($id, $columns = ['*'])
    {
        if (!is_numeric($id) || !is_int($id) || 0 >= $id) {
            throw (new ModelNotFoundException)->setModel(TestModel::class, $id);
        } else {
            return new self;
        }
    }

    public static function findMany(array $id, $columns = ['*'])
    {
        if (0 == count($id)) {
            throw (new ModelNotFoundException)->setModel(TestModel::class);
        } else {
            $result = [];
            for ($i = 0; $i < count($id); $i++) {
                $model     = new self;
                $model->id = $id[$i];
                $result[]  = $model;
            }
            return collect([$result]);
        }
    }


    public function setArtisan($value)
    {
        if (null === $value) {
            $this->isArtisan = null;
        }
        $this->isArtisan = boolval($value);
    }

    public function metadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int      $count
     * @param  callable $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->enforceOrderBy();

        $page = 1;

        do {
            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            $results = $this->forPage($page, $count)->get();

            $countResults = $results->count();

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results) === false) {
                return false;
            }

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Add a generic "order by" clause if the query doesn't already have one.
     *
     * @return void
     */
    protected function enforceOrderBy()
    {
        if (empty($this->query->orders) && empty($this->query->unionOrders)) {
            $this->orderBy($this->model->getQualifiedKeyName(), 'asc');
        }
    }

    public function isRunningInArtisan()
    {
        if (null !== $this->isArtisan) {
            return $this->isArtisan;
        }
        return $this->isArtisan();
    }
}
