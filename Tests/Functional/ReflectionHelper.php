<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Tests\Functional;

class ReflectionHelper
{
    /**
     * @param class-string $class
     */
    public static function getMethod(string $class, string $methodName): \ReflectionMethod
    {
        $method = (new \ReflectionClass($class))->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param class-string $class
     */
    public static function getProperty(string $class, string $propertyName): \ReflectionProperty
    {
        $property = (new \ReflectionClass($class))->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
