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
    protected static $afterExtract;
    protected static $afterUnify;
    protected static $afterVerify;
    protected static $afterImplement;

    public static function setAfterExtract(callable $method = null)
    {
        self::$afterExtract = $method;
    }

    public static function setAfterUnify(callable $method = null)
    {
        self::$afterUnify = $method;
    }

    public static function setAfterVerify(callable $method = null)
    {
        self::$afterVerify = $method;
    }

    public static function setAfterImplement(callable $method = null)
    {
        self::$afterImplement = $method;
    }

    /**
     * Encapsulate applying self::$after{FOO} calls.
     *
     * @param mixed         $parm
     * @param callable|null $func
     */
    private function handleCustomFunction($parm, callable $func = null)
    {
        if (null != $func) {
            $func($parm);
        }
    }
}
