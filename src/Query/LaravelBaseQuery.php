<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/09/19
 * Time: 4:55 PM.
 */
namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\Providers\Query\QueryResult;

abstract class LaravelBaseQuery
{
    /** @var AuthInterface */
    protected $auth;
    /** @var MetadataProvider */
    protected $metadataProvider;
    /** @var MetadataControllerContainer */
    protected $controllerContainer;
    /** @var ActionVerb[]  */
    protected $verbMap = [];

    /**
     * LaravelBaseQuery constructor.
     * @param AuthInterface|null $auth
     */
    public function __construct(AuthInterface $auth = null)
    {
        $this->auth                = isset($auth) ? $auth : new NullAuthProvider();
        $this->metadataProvider    = new MetadataProvider(App::make('app'));
        $this->controllerContainer = App::make('metadataControllers');
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

    /**
     * Dig out local copy of controller metadata mapping.
     *
     * @throws InvalidOperationException
     * @return MetadataControllerContainer
     */
    public function getControllerContainer()
    {
        if (null === $this->controllerContainer) {
            throw new InvalidOperationException('Controller container must not be null');
        }
        return $this->controllerContainer;
    }

    /**
     * @return ActionVerb[]
     */
    public function getVerbMap()
    {
        if (0 == count($this->verbMap)) {
            $this->verbMap['create'] = ActionVerb::CREATE();
            $this->verbMap['update'] = ActionVerb::UPDATE();
            $this->verbMap['delete'] = ActionVerb::DELETE();
        }
        return $this->verbMap;
    }

    /**
     * @return AuthInterface
     */
    protected function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param QueryResult|Model|Relation $sourceEntityInstance
     * @return Model|Relation|null
     */
    protected function unpackSourceEntity($sourceEntityInstance)
    {
        if ($sourceEntityInstance instanceof QueryResult) {
            $source = $sourceEntityInstance->results;
            $source = (is_array($source)) ? $source[0] : $source;
            return $source;
        }
        return $sourceEntityInstance;
    }
}
