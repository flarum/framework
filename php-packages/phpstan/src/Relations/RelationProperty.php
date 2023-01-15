<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Relations;

use Exception;
use Flarum\PHPStan\Extender\MethodCall;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class RelationProperty implements PropertyReflection
{
    /** @var MethodCall */
    private $methodCall;
    /** @var ClassReflection */
    private $classReflection;

    public function __construct(MethodCall $methodCall, ClassReflection $classReflection)
    {
        $this->methodCall = $methodCall;
        $this->classReflection = $classReflection;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflection;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getReadableType(): Type
    {
        switch ($this->methodCall->methodName) {
            case 'hasMany':
            case 'belongsToMany':
                return new GenericObjectType(Collection::class, [new ObjectType($this->methodCall->arguments[1]->class->toString())]);

            case 'hasOne':
            case 'belongsTo':
                return new ObjectType($this->methodCall->arguments[1]->class->toString());

            default:
                throw new Exception('Unknown relationship type for relation: '.$this->methodCall->methodName);
        }
    }

    public function getWritableType(): Type
    {
        return $this->getReadableType();
    }

    public function canChangeTypeAfterAssignment(): bool
    {
        return false;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
