<?php

namespace Bulk\Components\Extendable\Trait;

use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;
use Closure;

trait Extendable
{
    /**
     * The registered extends object
     *
     * @var array
     */
    protected static array $extends = [];

    /**
     * Register a custom macro.
     *
     * @param string $name
     * @param mixed $extend
     * 
     * @return void
     */
    public static function extend(string $name,mixed $extend): void
    {
        static::$extends[$name] = $extend;
    }

    /**
     * Mix another object into the class.
     *
     * @param mixed $mixin
     * @param bool $replace
     * 
     * @return void
     *
     * @throws \ReflectionException
     */
    public static function mixin(mixed $mixin, bool $replace = true): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || !static::isExtended($method->name)) {
                $method->setAccessible(true);
                static::extend($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Checks if extended object has registered
     *
     * @param string $name
     * 
     * @return bool
     */
    public static function isExtended(string $name): bool
    {
        return isset(static::$extends[$name]);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array $parameters
     * 
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        if (!static::isExtended($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $extend = static::$extends[$method];

        if ($extend instanceof Closure) {
            $extend = $extend->bindTo(null, static::class);
        }

        return $extend(...$parameters);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array $parameters
     * 
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (!static::isExtended($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $extend = static::$extends[$method];

        if ($extend instanceof Closure) {
            $extend = $extend->bindTo($this, static::class);
        }

        return $extend(...$parameters);
    }
}
