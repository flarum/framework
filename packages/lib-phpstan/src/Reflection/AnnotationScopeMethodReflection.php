<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Reflection;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

final class AnnotationScopeMethodReflection implements MethodReflection
{
    /** @var string */
    private $name;

    /** @var ClassReflection */
    private $declaringClass;

    /** @var Type */
    private $returnType;

    /** @var bool */
    private $isStatic;

    /** @var AnnotationScopeMethodParameterReflection[] */
    private $parameters;

    /** @var bool */
    private $isVariadic;

    /** @var FunctionVariant[]|null */
    private $variants = null;

    /**
     * @param  string  $name
     * @param  ClassReflection  $declaringClass
     * @param  Type  $returnType
     * @param  AnnotationScopeMethodParameterReflection[]  $parameters
     * @param  bool  $isStatic
     * @param  bool  $isVariadic
     */
    public function __construct(string $name, ClassReflection $declaringClass, Type $returnType, array $parameters, bool $isStatic, bool $isVariadic)
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
        $this->returnType = $returnType;
        $this->parameters = $parameters;
        $this->isStatic = $isStatic;
        $this->isVariadic = $isVariadic;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ParametersAcceptor[]
     */
    public function getVariants(): array
    {
        if ($this->variants === null) {
            $this->variants = [new FunctionVariant(TemplateTypeMap::createEmpty(), null, $this->parameters, $this->isVariadic, $this->returnType)];
        }

        return $this->variants;
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
        return TrinaryLogic::createMaybe();
    }

    public function getDocComment(): ?string
    {
        return null;
    }
}
