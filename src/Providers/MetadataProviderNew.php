<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;

class MetadataProviderNew extends MetadataProvider
{
    protected $relationHolder;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->relationHolder = new MetadataRelationHolder();
        self::$isBooted = false;
    }

    /**
     * @return MetadataRelationHolder
     */
    public function getRelationHolder()
    {
        return $this->relationHolder;
    }

    public function calculateRoundTripRelations()
    {
        $modelNames = $this->getCandidateModels();

        foreach ($modelNames as $name) {
            if (!$this->getRelationHolder()->hasClass($name)) {
                $model = new $name();
                $this->getRelationHolder()->addModel($model);
            }
        }

        return $this->getRelationHolder()->getRelations();
    }
}
