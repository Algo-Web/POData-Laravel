<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/02/20
 * Time: 9:34 PM.
 */
namespace AlgoWeb\PODataLaravel\Providers;

trait MetadataProviderStepTrait
{
    /** @var callable|null */
    protected static $afterExtract;
    /** @var callable|null */
    protected static $afterUnify;
    /** @var callable|null */
    protected static $afterVerify;
    /** @var callable|null */
    protected static $afterImplement;

    public static function setAfterExtract(callable $method = null): void
    {
        self::$afterExtract = $method;
    }

    public static function setAfterUnify(callable $method = null): void
    {
        self::$afterUnify = $method;
    }

    public static function setAfterVerify(callable $method = null): void
    {
        self::$afterVerify = $method;
    }

    public static function setAfterImplement(callable $method = null): void
    {
        self::$afterImplement = $method;
    }

    /**
     * Encapsulate applying self::$after{FOO} calls.
     *
     * @param mixed         $parm
     * @param callable|null $func
     *
     * @return mixed|null
     */
    private function handleCustomFunction($parm, callable $func = null)
    {
        if (null != $func) {
            $func($parm);
        }
    }
}
