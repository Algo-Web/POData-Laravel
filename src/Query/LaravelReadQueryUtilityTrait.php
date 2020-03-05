<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/02/20
 * Time: 10:54 PM.
 */
namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use Symfony\Component\Process\Exception\InvalidArgumentException;

trait LaravelReadQueryUtilityTrait
{
    /** @var string|null */
    protected $name;

    /**
     * @param  SkipTokenInfo             $skipToken
     * @param  Model|Builder             $sourceEntityInstance
     * @throws InvalidOperationException
     * @return mixed
     */
    protected function processSkipToken(SkipTokenInfo $skipToken, $sourceEntityInstance)
    {
        $parameters = [];
        $processed  = [];
        $segments   = $skipToken->getOrderByInfo()->getOrderByPathSegments();
        $values     = $skipToken->getOrderByKeysInToken();
        $numValues  = count($values);
        if ($numValues != count($segments)) {
            $msg = 'Expected ' . count($segments) . ', got '.$numValues;
            throw new InvalidOperationException($msg);
        }

        for ($i = 0; $i < $numValues; $i++) {
            $relation          = $segments[$i]->isAscending() ? '>' : '<';
            $name              = $segments[$i]->getSubPathSegments()[0]->getName();
            $rawValue          = is_string($values[$i][0]) ? $values[$i][0] : $values[$i][0]->toString();
            $parameters[$name] = ['direction' => $relation, 'value' => trim($rawValue, '\'')];
        }

        foreach ($parameters as $name => $line) {
            $processed[$name]     = ['direction' => $line['direction'], 'value' => $line['value']];
            $sourceEntityInstance = $sourceEntityInstance
                ->orWhere(
                    function (Builder $query) use ($processed) {
                        foreach ($processed as $key => $proc) {
                            $query->where($key, $proc['direction'], $proc['value']);
                        }
                    }
                );
            // now we've handled the later-in-order segment for this key, drop it back to equality in prep
            // for next key - same-in-order for processed keys and later-in-order for next
            $processed[$name]['direction'] = '=';
        }
        return $sourceEntityInstance;
    }

    /**
     * @param  ResourceSet          $resourceSet
     * @throws \ReflectionException
     * @return mixed
     */
    protected function getSourceEntityInstance(ResourceSet $resourceSet)
    {
        $entityClassName = $resourceSet->getResourceType()->getInstanceType()->name;
        return App::make($entityClassName);
    }

    /**
     * @param  Model|Relation|null       $source
     * @param  ResourceSet|null          $resourceSet
     * @throws \ReflectionException
     * @return Model|Relation|mixed|null
     */
    protected function checkSourceInstance($source, ResourceSet $resourceSet = null)
    {
        if (!(null == $source || $source instanceof Model || $source instanceof Relation)) {
            $msg = 'Source entity instance must be null, a model, or a relation.';
            throw new InvalidArgumentException($msg);
        }

        if (null == $source) {
            $source = $this->getSourceEntityInstance(/* @scrutinizer ignore-type */$resourceSet);
        }

        return $source;
    }

    /**
     * @param  Model|Relation|Builder    $sourceEntityInstance
     * @param  KeyDescriptor|null        $keyDescriptor
     * @throws InvalidOperationException
     */
    protected function processKeyDescriptor(&$sourceEntityInstance, KeyDescriptor $keyDescriptor = null): void
    {
        if ($keyDescriptor) {
            $table = ($sourceEntityInstance instanceof Model) ? $sourceEntityInstance->getTable() . '.' : '';
            foreach ($keyDescriptor->getValidatedNamedValues() as $key => $value) {
                $trimValue            = trim($value[0], '\'');
                $sourceEntityInstance = $sourceEntityInstance->where($table . $key, $trimValue);
            }
        }
    }

    /**
     * @param  string[]|null             $eagerLoad
     * @throws InvalidOperationException
     * @return array
     */
    protected function processEagerLoadList(array $eagerLoad = null): array
    {
        $load    = (null === $eagerLoad) ? [] : $eagerLoad;
        $rawLoad = [];
        foreach ($load as $line) {
            if (!is_string($line)) {
                throw new InvalidOperationException('Eager-load elements must be non-empty strings');
            }
            $lineParts     = explode('/', $line);
            $numberOfParts = count($lineParts);
            for ($i = 0; $i < $numberOfParts; $i++) {
                $lineParts[$i] = $this->getLaravelRelationName($lineParts[$i]);
            }
            $remixLine = implode('.', $lineParts);
            $rawLoad[] = $remixLine;
        }
        return $rawLoad;
    }

    /**
     * @param  string $odataProperty
     * @return string
     */
    protected function getLaravelRelationName(string $odataProperty)
    {
        $laravelProperty = $odataProperty;
        $pos             = strrpos($laravelProperty, '_');
        if ($pos !== false) {
            $laravelProperty = substr($laravelProperty, 0, $pos);
        }
        return $laravelProperty;
    }
}
