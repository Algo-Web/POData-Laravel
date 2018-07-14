<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use Illuminate\Routing\Controller as BaseController;
use POData\Common\InvalidOperationException;

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
        if (!$this instanceof BaseController) {
            throw new InvalidOperationException(get_class($this));
        }
        if (!$this instanceof BaseController) {
            throw new InvalidOperationException(get_class($this));
        }
        // enforce that mapping is actually not empty
        if (0 == count($this->mapping)) {
            throw new InvalidOperationException('Mapping array must not be empty');
        }

        if (!array_key_exists($modelName, $this->mapping)) {
            $msg = 'Metadata mapping for model ' . $modelName . ' not defined';
            throw new \Exception($msg);
        }

        $this->checkCrudVerbDefined($crudVerb);
        $isOptional = in_array($crudVerb, $this->optionalVerbs);

        $lookup = $this->mapping[$modelName];
        if (!is_array($lookup)) {
            $msg = 'Metadata mapping for model ' . $modelName . ' not an array';
            throw new \Exception($msg);
        }

        if (!array_key_exists($crudVerb, $lookup)) {
            if ($isOptional) {
                // optional crud verbs don't have to be defined - so we can return null
                return null;
            }
            $msg = 'Metadata mapping for CRUD verb ' . $crudVerb . ' on model ' . $modelName . ' not defined';
            throw new \Exception($msg);
        }
        $result = $lookup[$crudVerb];
        if (!isset($result)) {
            $msg = 'Metadata mapping for CRUD verb ' . $crudVerb . ' on model ' . $modelName . ' null';
            throw new \Exception($msg);
        }

        if (!method_exists($this, $result)) {
            $msg = 'Metadata target for CRUD verb ' . $crudVerb . ' on model ' . $modelName . ' does not exist';
            throw new \Exception($msg);
        }

        $class = get_class($this);
        $parmArray = $this->getParameterNames($result);

        return ['method' => $result, 'controller' => $class, 'parameters' => $parmArray];
    }

    public function getMappings()
    {
        // enforce we're actually hooked up to a controller
        if (!$this instanceof BaseController) {
            throw new InvalidOperationException(get_class($this));
        }
        // enforce that mapping is actually not empty
        if (empty($this->mapping)) {
            throw new InvalidOperationException('Mapping array must not be empty');
        }


        $allMappings = [];

        // check that mapping array is well formed and sane, rather than waiting to stab us with a spatula
        foreach ($this->mapping as $key => $map) {
            if (!is_array($map)) {
                $msg = 'Metadata mapping for model ' . $key . ' not an array';
                throw new \Exception($msg);
            }
            foreach ($map as $verb => $method) {
                $this->checkCrudVerbDefined($verb);
                if (!isset($method)) {
                    $msg = 'Metadata mapping for CRUD verb ' . $verb . ' on model ' . $key . ' null';
                    throw new \Exception($msg);
                }

                if (!method_exists($this, $method)) {
                    $msg = 'Metadata target for CRUD verb ' . $verb . ' on model ' . $key . ' does not exist';
                    throw new \Exception($msg);
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
        if (!is_string($crudVerb)) {
            throw new InvalidOperationException('');
        }
        $lowVerb = strtolower($crudVerb);
        if (!in_array($lowVerb, $this->crudVerbs) && !in_array($crudVerb, $this->optionalVerbs)) {
            $msg = 'CRUD verb ' . $crudVerb . ' not defined';
            throw new \Exception($msg);
        }
    }
}
