<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class MetadataRelationHolder
{
    protected $multConstraints = [ '0..1' => ['1'], '1' => ['0..1', '*'], '*' => ['1', '*']];
    protected $relations = [];

    public function __construct()
    {
    }

    /**
     * Add model's relationships to holder
     *
     * @param Model $model
     */
    public function addModel(Model $model)
    {
        if (!in_array('AlgoWeb\\PODataLaravel\\Models\\MetadataTrait', class_uses($model))) {
            $msg = 'Supplied model does not use MetadataTrait';
            throw new \InvalidArgumentException($msg);
        }
        $className = get_class($model);
        if (array_key_exists($className, $this->relations)) {
            $msg = $className.' already added';
            throw new \InvalidArgumentException($msg);
        }
        $this->relations[$className] = $model->getRelationships();
    }

    public function getRelationsByKey($className, $keyName)
    {
        $this->checkClassExists($className);

        $rels = $this->relations[$className];
        if (!array_key_exists($keyName, $rels)) {
            $msg = 'Key ' . $keyName . ' not registered on ' . $className;
            throw new \InvalidArgumentException($msg);
        }

        $result = [];
        $payload = $rels[$keyName];
        $principalType = $className;
        foreach ($payload as $dependentType => $targDeets) {
            if (!array_key_exists($dependentType, $this->relations)) {
                continue;
            }
            // if principal and ostensible dependent type are equal, drop through to specific handler
            // at moment, this is only for morphTo relations - morphedByMany doesn't cause this
            if ($principalType === $dependentType) {
                $morphToLines = $this->getMorphToRelations($principalType, $targDeets, $keyName);
                foreach ($morphToLines as $morph) {
                    if (!in_array($morph, $result)) {
                        $result[] = $morph;
                    }
                }
                continue;
            }

            $foreign = $this->relations[$dependentType];

            foreach ($targDeets as $principalProperty => $rawDeets) {
                $targKey = $rawDeets['local'];
                $principalMult = $rawDeets['multiplicity'];
                $principalProperty = $rawDeets['property'];
                if (!array_key_exists($targKey, $foreign)) {
                    continue;
                }
                $foreignDeets = $foreign[$targKey];
                foreach ($foreignDeets as $foreignType => $raw) {
                    if (!array_key_exists($foreignType, $this->relations)) {
                        continue;
                    }
                    foreach ($raw as $dependentProperty => $dependentPayload) {
                        if ($keyName == $dependentPayload['local']) {
                            $dependentMult = $dependentPayload['multiplicity'];
                            // generate forward and reverse relations
                            list($forward, $reverse) = $this->calculateRoundTripRelationsGenForwardReverse(
                                $principalType,
                                $principalMult,
                                $principalProperty,
                                $dependentType,
                                $dependentMult,
                                $dependentProperty
                            );
                            if (!in_array($forward, $result)) {
                                // add forward relation
                                $result[] = $forward;
                            }
                            if (!in_array($reverse, $result)) {
                                // add reverse relation
                                $result[] = $reverse;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function getRelationsByClass($className)
    {
        $this->checkClassExists($className);

        $rels = $this->relations[$className];
        $keys = array_keys($rels);
        $results = [];
        foreach ($keys as $key) {
            $lines = $this->getRelationsByKey($className, $key);
            foreach ($lines as $line) {
                if (!in_array($line, $results)) {
                    $results[] = $line;
                }
            }
        }

        return $results;
    }

    private function getMorphToRelations($principalType, $targDeets, $keyName)
    {
        $result = [];
        $deetKeys = array_keys($targDeets);
        $principalProperty = $deetKeys[0];
        $principalDeets = $targDeets[$principalProperty];
        $principalMult = $principalDeets['multiplicity'];

        foreach ($this->relations as $dependentType => $dependentDeets) {
            foreach ($dependentDeets as $targKey => $rawDeets) {
                foreach ($rawDeets as $targType => $interDeets) {
                    if ($targType != $principalType) {
                        continue;
                    }
                    foreach ($interDeets as $dependentProperty => $finalDeets) {
                        if ($keyName !== $finalDeets['local']) {
                            continue;
                        }
                        $dependentMult = $finalDeets['multiplicity'];
                        list($forward, $reverse) = $this->calculateRoundTripRelationsGenForwardReverse(
                            $principalType,
                            $principalMult,
                            $principalProperty,
                            $dependentType,
                            $dependentMult,
                            $dependentProperty
                        );
                        if (!in_array($forward, $result)) {
                            // add forward relation
                            $result[] = $forward;
                        }
                        if (!in_array($reverse, $result)) {
                            // add reverse relation
                            $result[] = $reverse;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $principalType
     * @param $principalMult
     * @param $principalProperty
     * @param $dependentType
     * @param $dependentMult
     * @param $dependentProperty
     * @return array[]
     */
    private function calculateRoundTripRelationsGenForwardReverse(
        $principalType,
        $principalMult,
        $principalProperty,
        $dependentType,
        $dependentMult,
        $dependentProperty
    ) {
        assert(
            in_array($dependentMult, $this->multConstraints[$principalMult]),
            'Cannot pair multiplicities ' . $dependentMult . ' and ' . $principalMult
        );
        assert(
            in_array($principalMult, $this->multConstraints[$dependentMult]),
            'Cannot pair multiplicities ' . $principalMult . ' and ' . $dependentMult
        );
        $forward = [
            'principalType' => $principalType,
            'principalMult' => $dependentMult,
            'principalProp' => $principalProperty,
            'dependentType' => $dependentType,
            'dependentMult' => $principalMult,
            'dependentProp' => $dependentProperty
        ];
        $reverse = [
            'principalType' => $dependentType,
            'principalMult' => $principalMult,
            'principalProp' => $dependentProperty,
            'dependentType' => $principalType,
            'dependentMult' => $dependentMult,
            'dependentProp' => $principalProperty
        ];
        return [$forward, $reverse];
    }

    /**
     * @param $className
     */
    protected function checkClassExists($className)
    {
        if (!array_key_exists($className, $this->relations)) {
            $msg = $className . ' does not exist in holder';
            throw new \InvalidArgumentException($msg);
        }
    }

    /**
     * Reset stored relations
     */
    public function reset()
    {
        $this->relations = [];
    }
}
