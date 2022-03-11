<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;

final class ReflectionHelper
{
    /**
     * Does the given class or any of its ancestors have an `@property*` annotation with the given name?
     */
    public static function hasPropertyTag(ClassReflection $classReflection, string $propertyName): bool
    {
        if (array_key_exists($propertyName, $classReflection->getPropertyTags())) {
            return true;
        }

        foreach ($classReflection->getAncestors() as $ancestor) {
            if (array_key_exists($propertyName, $ancestor->getPropertyTags())) {
                return true;
            }
        }

        return false;
    }
}
