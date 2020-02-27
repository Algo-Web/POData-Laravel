<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 3:52 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider;

class DummyMetadataControllerProvider extends MetadataControllerProvider
{
    /**
     * @param  array      $classes
     * @throws \Exception
     * @return array
     */
    public function getCandidateControllers(array $classes)
    {
        return parent::getCandidateControllers($classes);
    }
}
