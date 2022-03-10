<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Properties;

use ArrayObject;
use Flarum\PHPStan\Reflection\ReflectionHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\IntegerType;

/**
 * @internal
 */
final class ModelPropertyExtension implements PropertiesClassReflectionExtension
{
    /** @var array<string, SchemaTable> */
    private $tables = [];

    /** @var TypeStringResolver */
    private $stringResolver;

    /** @var string */
    private $dateClass;

    /** @var MigrationHelper */
    private $migrationHelper;

    public function __construct(TypeStringResolver $stringResolver, MigrationHelper $migrationHelper)
    {
        $this->stringResolver = $stringResolver;
        $this->migrationHelper = $migrationHelper;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if ($classReflection->isAbstract()) {
            return false;
        }

        if ($classReflection->hasNativeMethod('get'.Str::studly($propertyName).'Attribute')) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
        }

        if (count($this->tables) === 0) {
            $this->tables = $this->migrationHelper->initializeTables();
        }

        if ($propertyName === 'id') {
            return true;
        }

        $modelName = $classReflection->getNativeReflection()->getName();

        try {
            $reflect = new \ReflectionClass($modelName);

            /** @var Model $modelInstance */
            $modelInstance = $reflect->newInstanceWithoutConstructor();

            $tableName = $modelInstance->getTable();
        } catch (\ReflectionException $e) {
            return false;
        }

        if (! array_key_exists($tableName, $this->tables)) {
            return false;
        }

        if (! array_key_exists($propertyName, $this->tables[$tableName]->columns)) {
            return false;
        }

        $this->castPropertiesType($modelInstance);

        $column = $this->tables[$tableName]->columns[$propertyName];

        [$readableType, $writableType] = $this->getReadableAndWritableTypes($column, $modelInstance);

        $column->readableType = $readableType;
        $column->writeableType = $writableType;

        $this->tables[$tableName]->columns[$propertyName] = $column;

        return true;
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        $modelName = $classReflection->getNativeReflection()->getName();

        try {
            $reflect = new \ReflectionClass($modelName);

            /** @var Model $modelInstance */
            $modelInstance = $reflect->newInstanceWithoutConstructor();

            $tableName = $modelInstance->getTable();
        } catch (\ReflectionException $e) {
            // `hasProperty` should return false if there was a reflection exception.
            // so this should never happen
            throw new ShouldNotHappenException();
        }

        if (
            (
                ! array_key_exists($tableName, $this->tables)
                || ! array_key_exists($propertyName, $this->tables[$tableName]->columns)
            )
            && $propertyName === 'id'
        ) {
            return new ModelProperty(
                $classReflection,
                new IntegerType(),
                new IntegerType()
            );
        }

        $column = $this->tables[$tableName]->columns[$propertyName];

        return new ModelProperty(
            $classReflection,
            $this->stringResolver->resolve($column->readableType),
            $this->stringResolver->resolve($column->writeableType)
        );
    }

    private function getDateClass(): string
    {
        if (! $this->dateClass) {
            $this->dateClass = class_exists(\Illuminate\Support\Facades\Date::class)
                ? '\\'.get_class(\Illuminate\Support\Facades\Date::now())
                : '\Illuminate\Support\Carbon';

            $this->dateClass .= '|\Carbon\Carbon';
        }

        return $this->dateClass;
    }

    /**
     * @param  SchemaColumn  $column
     * @param  Model  $modelInstance
     * @return string[]
     * @phpstan-return array<int, string>
     */
    private function getReadableAndWritableTypes(SchemaColumn $column, Model $modelInstance): array
    {
        $readableType = $column->readableType;
        $writableType = $column->writeableType;

        if (in_array($column->name, $modelInstance->getDates(), true)) {
            return [$this->getDateClass().($column->nullable ? '|null' : ''), $this->getDateClass().'|string'.($column->nullable ? '|null' : '')];
        }

        switch ($column->readableType) {
            case 'string':
            case 'int':
            case 'float':
                $readableType = $writableType = $column->readableType.($column->nullable ? '|null' : '');
                break;

            case 'boolean':
            case 'bool':
                switch ((string) config('database.default')) {
                    case 'sqlite':
                    case 'mysql':
                        $writableType = '0|1|bool';
                        $readableType = 'bool';
                        break;
                    default:
                        $readableType = $writableType = 'bool';
                        break;
                }
                break;
            case 'enum':
                if (! $column->options) {
                    $readableType = $writableType = 'string';
                } else {
                    $readableType = $writableType = '\''.implode('\'|\'', $column->options).'\'';
                }

                break;

            default:
                break;
        }

        return [$readableType, $writableType];
    }

    private function castPropertiesType(Model $modelInstance): void
    {
        $casts = $modelInstance->getCasts();
        foreach ($casts as $name => $type) {
            if (! array_key_exists($name, $this->tables[$modelInstance->getTable()]->columns)) {
                continue;
            }

            switch ($type) {
                case 'boolean':
                case 'bool':
                    $realType = 'boolean';
                    break;
                case 'string':
                    $realType = 'string';
                    break;
                case 'array':
                case 'json':
                    $realType = 'array';
                    break;
                case 'object':
                    $realType = 'object';
                    break;
                case 'int':
                case 'integer':
                case 'timestamp':
                    $realType = 'integer';
                    break;
                case 'real':
                case 'double':
                case 'float':
                    $realType = 'float';
                    break;
                case 'date':
                case 'datetime':
                    $realType = $this->getDateClass();
                    break;
                case 'collection':
                    $realType = '\Illuminate\Support\Collection';
                    break;
                case 'Illuminate\Database\Eloquent\Casts\AsArrayObject':
                    $realType = ArrayObject::class;
                    break;
                case 'Illuminate\Database\Eloquent\Casts\AsCollection':
                    $realType = '\Illuminate\Support\Collection<mixed, mixed>';
                    break;
                default:
                    $realType = class_exists($type) ? ('\\'.$type) : 'mixed';
                    break;
            }

            if ($this->tables[$modelInstance->getTable()]->columns[$name]->nullable) {
                $realType .= '|null';
            }

            $this->tables[$modelInstance->getTable()]->columns[$name]->readableType = $realType;
            $this->tables[$modelInstance->getTable()]->columns[$name]->writeableType = $realType;
        }
    }
}
