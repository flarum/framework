<?php

declare(strict_types=1);

namespace Flarum\PHPStan\Reflection;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class DynamicWhereParameterReflection implements ParameterReflection
{
    public function getName(): string
    {
        return 'dynamic-where-parameter';
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function getType(): Type
    {
        return new MixedType();
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function getDefaultValue(): ?Type
    {
        return null;
    }
}
