<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Database\AbstractModel;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class Model implements ExtenderInterface
{
    private $modelClass;
    private $customRelations = [];

    /**
     * @param string $modelClass: The ::class attribute of the model you are modifying.
     *                           This model should extend from \Flarum\Database\AbstractModel.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Add an attribute to be treated as a date.
     *
     * @param string $attribute
     * @return self
     */
    public function dateAttribute(string $attribute): self
    {
        Arr::set(
            AbstractModel::$dateAttributes,
            $this->modelClass,
            array_merge(
                Arr::get(AbstractModel::$dateAttributes, $this->modelClass, []),
                [$attribute]
            )
        );

        return $this;
    }

    /**
     * Add a default value for a given attribute, which can be an explicit value, a closure,
     * or an instance of an invokable class. Unlike with some other extenders,
     * it CANNOT be the `::class` attribute of an invokable class.
     *
     * @param string $attribute
     * @param mixed $value
     * @return self
     */
    public function default(string $attribute, $value): self
    {
        Arr::set(AbstractModel::$defaults, "$this->modelClass.$attribute", $value);

        return $this;
    }

    /**
     * Establish a simple belongsTo relationship from this model to another model.
     * This represents an inverse one-to-one or inverse one-to-many relationship.
     * For more complex relationships, use the ->relationship method.
     *
     * @param string $name: The name of the relation. This doesn't have to be anything in particular,
     *                      but has to be unique from other relation names for this model, and should
     *                      work as the name of a method.
     * @param string $related: The ::class attribute of the model, which should extend \Flarum\Database\AbstractModel.
     * @param string $foreignKey: The foreign key attribute of the parent model.
     * @param string $ownerKey: The primary key attribute of the parent model.
     * @return self
     */
    public function belongsTo(string $name, string $related, string $foreignKey = null, string $ownerKey = null): self
    {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $foreignKey, $ownerKey, $name) {
            return $model->belongsTo($related, $foreignKey, $ownerKey, $name);
        });
    }

    /**
     * Establish a simple belongsToMany relationship from this model to another model.
     * This represents a many-to-many relationship.
     * For more complex relationships, use the ->relationship method.
     *
     * @param string $name: The name of the relation. This doesn't have to be anything in particular,
     *                      but has to be unique from other relation names for this model, and should
     *                      work as the name of a method.
     * @param string $related: The ::class attribute of the model, which should extend \Flarum\Database\AbstractModel.
     * @param string $table: The intermediate table for this relation
     * @param string $foreignPivotKey: The foreign key attribute of the parent model.
     * @param string $relatedPivotKey: The associated key attribute of the relation.
     * @param string $parentKey: The key name of the parent model.
     * @param string $relatedKey: The key name of the related model.
     * @return self
     */
    public function belongsToMany(
        string $name,
        string $related,
        string $table = null,
        string $foreignPivotKey = null,
        string $relatedPivotKey = null,
        string $parentKey = null,
        string $relatedKey = null
    ): self {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $name) {
            return $model->belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $name);
        });
    }

    /**
     * Establish a simple hasOne relationship from this model to another model.
     * This represents a one-to-one relationship.
     * For more complex relationships, use the ->relationship method.
     *
     * @param string $name: The name of the relation. This doesn't have to be anything in particular,
     *                      but has to be unique from other relation names for this model, and should
     *                      work as the name of a method.
     * @param string $related: The ::class attribute of the model, which should extend \Flarum\Database\AbstractModel.
     * @param string $foreignKey: The foreign key attribute of the parent model.
     * @param string $localKey: The primary key attribute of the parent model.
     * @return self
     */
    public function hasOne(string $name, string $related, string $foreignKey = null, string $localKey = null): self
    {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $foreignKey, $localKey) {
            return $model->hasOne($related, $foreignKey, $localKey);
        });
    }

    /**
     * Establish a simple hasMany relationship from this model to another model.
     * This represents a one-to-many relationship.
     * For more complex relationships, use the ->relationship method.
     *
     * @param string $name: The name of the relation. This doesn't have to be anything in particular,
     *                      but has to be unique from other relation names for this model, and should
     *                      work as the name of a method.
     * @param string $related: The ::class attribute of the model, which should extend \Flarum\Database\AbstractModel.
     * @param string $foreignKey: The foreign key attribute of the parent model.
     * @param string $localKey: The primary key attribute of the parent model.
     * @return self
     */
    public function hasMany(string $name, string $related, string $foreignKey = null, string $localKey = null): self
    {
        return $this->relationship($name, function (AbstractModel $model) use ($related, $foreignKey, $localKey) {
            return $model->hasMany($related, $foreignKey, $localKey);
        });
    }

    /**
     * Add a relationship from this model to another model.
     *
     * @param string $name: The name of the relation. This doesn't have to be anything in particular,
     *                      but has to be unique from other relation names for this model, and should
     *                      work as the name of a method.
     * @param callable|string $callback
     *
     * The callable can be a closure or invokable class, and should accept:
     * - $instance: An instance of this model.
     *
     * The callable should return:
     * - $relationship: A Laravel Relationship object. See relevant methods of models
     *                  like \Flarum\User\User for examples of how relationships should be returned.
     *
     * @return self
     */
    public function relationship(string $name, $callback): self
    {
        $this->customRelations[$name] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->customRelations as $name => $callback) {
            Arr::set(AbstractModel::$customRelations, "$this->modelClass.$name", ContainerUtil::wrapCallback($callback, $container));
        }
    }
}
