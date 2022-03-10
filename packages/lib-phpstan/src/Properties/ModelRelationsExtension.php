<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Properties;

use Flarum\PHPStan\Concerns;
use Flarum\PHPStan\Methods\BuilderHelper;
use Flarum\PHPStan\Reflection\ReflectionHelper;
use Flarum\PHPStan\Types\RelationParserHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;

/**
 * @internal
 */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension
{
    use Concerns\HasContainer;

    /** @var RelationParserHelper */
    private $relationParserHelper;

    /** @var BuilderHelper */
    private $builderHelper;

    public function __construct(
        RelationParserHelper $relationParserHelper,
        BuilderHelper $builderHelper
    ) {
        $this->relationParserHelper = $relationParserHelper;
        $this->builderHelper = $builderHelper;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
        }

        $hasNativeMethod = $classReflection->hasNativeMethod($propertyName);

        if (! $hasNativeMethod) {
            return false;
        }

        $returnType = ParametersAcceptorSelector::selectSingle($classReflection->getNativeMethod($propertyName)->getVariants())->getReturnType();

        if (! (new ObjectType(Relation::class))->isSuperTypeOf($returnType)->yes()) {
            return false;
        }

        return true;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $method = $classReflection->getMethod($propertyName, new OutOfClassScope());

        /** @var ObjectType $returnType */
        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if ($returnType instanceof GenericObjectType) {
            /** @var ObjectType $relatedModelType */
            $relatedModelType = $returnType->getTypes()[0];
            $relatedModelClassName = $relatedModelType->getClassName();
        } else {
            $relatedModelClassName = $this
                ->relationParserHelper
                ->findRelatedModelInRelationMethod($method);
        }

        if ($relatedModelClassName === null) {
            $relatedModelClassName = Model::class;
        }

        $relatedModel = new ObjectType($relatedModelClassName);
        $collectionClass = $this->builderHelper->determineCollectionClassName($relatedModelClassName);

        if (Str::contains($returnType->getClassName(), 'Many')) {
            return new ModelProperty(
                $classReflection,
                new GenericObjectType($collectionClass, [$relatedModel]),
                new NeverType(),
                false
            );
        }

        if (Str::endsWith($returnType->getClassName(), 'MorphTo')) {
            return new ModelProperty($classReflection, new UnionType([
                new ObjectType(Model::class),
                new MixedType(),
            ]), new NeverType(), false);
        }

        return new ModelProperty($classReflection, new UnionType([
            $relatedModel,
            new NullType(),
        ]), new NeverType(), false);
    }
}
