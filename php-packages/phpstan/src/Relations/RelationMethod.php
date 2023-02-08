<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Relations;

use Flarum\PHPStan\Extender\MethodCall;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class RelationMethod implements MethodReflection
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

    public function getName(): string
    {
        return $this->methodCall->arguments[0]->value;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    public function getVariants(): array
    {
        $returnType = 'Illuminate\Database\Eloquent\Relations\Relation';

        switch ($this->methodCall->methodName) {
            case 'belongsTo':
                $returnType = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
                break;
            case 'belongsToMany':
                $returnType = 'Illuminate\Database\Eloquent\Relations\BelongsToMany';
                break;
            case 'hasMany':
                $returnType = 'Illuminate\Database\Eloquent\Relations\HasMany';
                break;
            case 'hasOne':
                $returnType = 'Illuminate\Database\Eloquent\Relations\HasOne';
                break;
        }

        $relationTarget = $this->methodCall->arguments[1]->class->toString();

        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                [],
                false,
                new GenericObjectType($returnType, [new ObjectType($relationTarget)])
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
