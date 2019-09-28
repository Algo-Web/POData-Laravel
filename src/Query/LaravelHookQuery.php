<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceSet;

class LaravelHookQuery
{
    protected $auth;
    protected $metadataProvider;

    public function __construct(AuthInterface $auth = null)
    {
        $this->auth = isset($auth) ? $auth : new NullAuthProvider();
        $this->metadataProvider = new MetadataProvider(App::make('app'));
    }

    /**
     * Attaches child model to parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     *
     * @throws InvalidOperationException
     */
    public function hookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        $relation = $this->isModelHookInputsOk($sourceEntityInstance, $targetEntityInstance, $navPropName);

        // in case the fake 'PrimaryKey' attribute got set inbound for a polymorphic-affected model, flatten it now
        unset($targetEntityInstance->PrimaryKey);

        if ($relation instanceof BelongsTo) {
            $relation->associate($targetEntityInstance);
        } elseif ($relation instanceof BelongsToMany) {
            $relation->attach($targetEntityInstance);
        } elseif ($relation instanceof HasOneOrMany) {
            $relation->save($targetEntityInstance);
        }
        LaravelQuery::queueModel($sourceEntityInstance);
        LaravelQuery::queueModel($targetEntityInstance);
        return true;
    }

    /**
     * Removes child model from parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     * @throws InvalidOperationException
     */
    public function unhookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        $relation = $this->isModelHookInputsOk($sourceEntityInstance, $targetEntityInstance, $navPropName);

        // in case the fake 'PrimaryKey' attribute got set inbound for a polymorphic-affected model, flatten it now
        unset($targetEntityInstance->PrimaryKey);

        $changed = false;

        if ($relation instanceof BelongsTo) {
            $relation->dissociate();
            $changed = true;
        } elseif ($relation instanceof BelongsToMany) {
            $relation->detach($targetEntityInstance);
            $changed = true;
        } elseif ($relation instanceof HasOneOrMany) {
            // dig up inverse property name, so we can pass it to unhookSingleModel with source and target elements
            // swapped
            $otherPropName = $this->getMetadataProvider()
                ->resolveReverseProperty($sourceEntityInstance, $navPropName);
            if (null === $otherPropName) {
                $srcClass = get_class($sourceEntityInstance);
                $msg = 'Bad navigation property, ' . $navPropName . ', on source model ' . $srcClass;
                throw new \InvalidArgumentException($msg);
            }
            $this->unhookSingleModel(
                $targetResourceSet,
                $targetEntityInstance,
                $sourceResourceSet,
                $sourceEntityInstance,
                $otherPropName
            );
        }
        if ($changed) {
            LaravelQuery::queueModel($sourceEntityInstance);
            LaravelQuery::queueModel($targetEntityInstance);
        }
        return true;
    }

    /**
     * @param $sourceEntityInstance
     * @param $targetEntityInstance
     * @param $navPropName
     * @throws \InvalidArgumentException
     * @throws InvalidOperationException
     * @return Relation
     */
    protected function isModelHookInputsOk($sourceEntityInstance, $targetEntityInstance, $navPropName)
    {
        if (!$sourceEntityInstance instanceof Model || !$targetEntityInstance instanceof Model) {
            $msg = 'Both input entities must be Eloquent models';
            throw new InvalidOperationException($msg);
        }
        $relation = $sourceEntityInstance->$navPropName();
        if (!$relation instanceof Relation) {
            $msg = 'Navigation property must be an Eloquent relation';
            throw new \InvalidArgumentException($msg);
        }
        $targType = $relation->getRelated();
        if (!$targetEntityInstance instanceof $targType) {
            $msg = 'Target instance must be of type compatible with relation declared in method ' . $navPropName;
            throw new \InvalidArgumentException($msg);
        }
        return $relation;
    }

    /**
     * Dig out local copy of POData-Laravel metadata provider.
     *
     * @return MetadataProvider
     */
    public function getMetadataProvider()
    {
        return $this->metadataProvider;
    }
}
