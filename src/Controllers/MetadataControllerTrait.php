<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use Illuminate\Routing\Controller as BaseController;

trait MetadataControllerTrait
{
    /*
     * Allowed crud verbs
     */
    protected $crudVerbs = ['create', 'read', 'update', 'delete'];

    /*
     * Optional crud verbs - if these are unset in mapping array, LaravelQuery drops through to default handler
     */
    protected $optionalVerbs = ['bulkCreate', 'bulkUpdate'];

    /*
     * Array to record mapping betweeen model-verb combos and names
     * First-level key is fully-qualified model name
     * (eg Alt\Swedish\Chef\Bork\Bork\Bork)
     * Second-level key is CRUD verb
     */
    protected $mapping;

    /*
     * Given model and verb, get method name and parameter list
     *
     * @param $modelName
     * @param $crudVerb
     * @return null|array
     * @throws \Exception
     */
    public function getMethodName($modelName, $crudVerb)
    {
        // enforce we're actually hooked up to a controller
        assert($this instanceof BaseController, get_class($this));
        // enforce that mapping is actually not empty
        assert(0 < count($this->mapping), 'Mapping array must not be empty');

        if (!array_key_exists($modelName, $this->mapping)) {
            throw new \Exception('Metadata mapping for model ' . $modelName . ' not defined');
        }

        $this->checkCrudVerbDefined($crudVerb);
        $isOptional = in_array($crudVerb, $this->optionalVerbs);

        $lookup = $this->mapping[$modelName];
        if (!is_array($lookup)) {
            throw new \Exception('Metadata mapping for model ' . $modelName . ' not an array');
        }

        if (!array_key_exists($crudVerb, $lookup)) {
            if ($isOptional) {
                // optional crud verbs don't have to be defined - so we can return null
                return null;
            }
            throw new \Exception('Metadata mapping for CRUD verb ' . $crudVerb . ' on model ' . $modelName . ' not defined');
        }
        $result = $lookup[$crudVerb];
        if (!isset($result)) {
            throw new \Exception('Metadata mapping for CRUD verb ' . $crudVerb . ' on model ' . $modelName . ' null');
        }

        if (!method_exists($this, $result)) {
            throw new \Exception(
                'Metadata target for CRUD verb ' . $crudVerb . ' on model ' . $modelName . ' does not exist'
            );
        }

        $class = get_class($this);
        $parmArray = $this->getParameterNames($result);

        return ['method' => $result, 'controller' => $class, 'parameters' => $parmArray];
    }

    public function getMappings()
    {
        // enforce we're actually hooked up to a controller
        assert($this instanceof BaseController, get_class($this));
        // enforce that mapping is actually not empty
        assert(!empty($this->mapping), 'Mapping array must not be empty');

        $allMappings = [];

        // check that mapping array is well formed and sane, rather than waiting to stab us with a spatula
        foreach ($this->mapping as $key => $map) {
            if (!is_array($map)) {
                throw new \Exception('Metadata mapping for model ' . $key . ' not an array');
            }
            foreach ($map as $verb => $method) {
                $this->checkCrudVerbDefined($verb);
                if (!isset($method)) {
                    throw new \Exception('Metadata mapping for CRUD verb ' . $verb . ' on model ' . $key . ' null');
                }

                if (!method_exists($this, $method)) {
                    throw new \Exception(
                        'Metadata target for CRUD verb ' . $verb . ' on model ' . $key . ' does not exist'
                    );
                }
                $parmArray = $this->getParameterNames($method);
                if (!array_key_exists($key, $allMappings)) {
                    $allMappings[$key] = [];
                }

                $class = get_class($this);
                $allMappings[$key][$verb] = ['method' => $method, 'controller' => $class, 'parameters' => $parmArray];
            }
        }
        // bolt on optional, undefined mappings - empty mappings will need to be deduplicated in metadata controller
        // provider
        $mapKeys = array_keys($this->mapping);
        foreach ($mapKeys as $map) {
            $undefined = array_diff($this->optionalVerbs, array_keys($this->mapping[$map]));
            foreach ($undefined as $undef) {
                $allMappings[$map][$undef] = null;
            }
        }

        return $allMappings;
    }

    /**
     * @param $result
     * @return array
     */
    protected function getParameterNames($result)
    {
        $parmArray = [];
        $reflec = new \ReflectionMethod($this, $result);
        $params = $reflec->getParameters();
        foreach ($params as $parm) {
            $detail = [];
            $detail['name'] = $parm->name;
            $classHint = $parm->getClass();
            $isRequest = false;
            if (null != $classHint) {
                // check to see if this is a request
                $className = $classHint->name;
                $class = new $className();
                $isRequest = $class instanceof \Illuminate\Http\Request;
                $detail['type'] = $className;
            }

            $detail['isRequest'] = $isRequest;
            $parmArray[$parm->name] = $detail;
        }
        return $parmArray;
    }

    /**
     * @param string $crudVerb
     *
     * @throws \Exception
     */
    private function checkCrudVerbDefined($crudVerb)
    {
        assert(is_string($crudVerb));
        $lowVerb = strtolower($crudVerb);
        if (!in_array($lowVerb, $this->crudVerbs) && !in_array($crudVerb, $this->optionalVerbs)) {
            throw new \Exception('CRUD verb ' . $crudVerb . ' not defined');
        }
    }
}
