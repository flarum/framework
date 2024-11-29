<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Schema\Contracts\RelationAggregator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Schema\Field\Attribute;
use Tobyz\JsonApiServer\Schema\Field\Field;
use Tobyz\JsonApiServer\Schema\Field\Relationship;
use Tobyz\JsonApiServer\Schema\Field\ToMany;
use Tobyz\JsonApiServer\Schema\Type\DateTime;

/**
 * @template M of Model
 * @extends AbstractResource<M>
 */
abstract class AbstractDatabaseResource extends AbstractResource implements
    Contracts\Findable,
    Contracts\Listable,
    Contracts\Countable,
    Contracts\Paginatable,
    Contracts\Creatable,
    Contracts\Updatable,
    Contracts\Deletable
{
    abstract public function model(): string;

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function resource(object $model, Context $context): ?string
    {
        $baseModel = $this->model();

        if ($model instanceof $baseModel) {
            return $this->type();
        }

        return null;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function getId(object $model, Context $context): string
    {
        return $model->getKey();
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function getValue(object $model, Field $field, Context $context): mixed
    {
        if ($field instanceof Relationship) {
            return $this->getRelationshipValue($model, $field, $context);
        } else {
            return $this->getAttributeValue($model, $field, $context);
        }
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    protected function getAttributeValue(Model $model, Field $field, Context $context): mixed
    {
        if ($field instanceof RelationAggregator && ($aggregate = $field->getRelationAggregate())) {
            $relationName = $aggregate['relation'];

            if (! $model->isRelation($relationName)) {
                return $model->getAttribute($this->property($field));
            }

            /** @var Relationship|null $relationship */
            $relationship = collect($context->fields($this))->first(fn ($f) => $f->name === $relationName);

            EloquentBuffer::add($model, $relationName, $aggregate);

            return function () use ($model, $relationName, $relationship, $field, $context, $aggregate) {
                if (! $model->hasAttribute($this->property($field))) {
                    EloquentBuffer::load($model, $relationName, $relationship, $context, $aggregate);
                }

                return $model->getAttribute($this->property($field));
            };
        }

        return $model->getAttribute($this->property($field));
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    protected function getRelationshipValue(Model $model, Relationship $field, Context $context): mixed
    {
        $method = $this->method($field);

        if ($model->isRelation($method)) {
            $relation = $model->$method();

            // If this is a belongs-to relationship, and we only need to get the ID
            // for linkage, then we don't have to actually load the relation because
            // the ID is stored in a column directly on the model. We will mock up a
            // related model with the value of the ID filled.
            if ($relation instanceof BelongsTo && $context->include === null) {
                if ($key = $model->getAttribute($relation->getForeignKeyName())) {
                    if ($relation instanceof MorphTo) {
                        $morphType = $model->{$relation->getMorphType()};
                        $related = $relation->createModelByType($morphType);
                    } else {
                        $related = $relation->getRelated();
                    }

                    return $related->newInstance()->forceFill([$related->getKeyName() => $key]);
                }

                return null;
            }

            EloquentBuffer::add($model, $method);

            return function () use ($model, $method, $field, $context) {
                if (! $model->relationLoaded($method)) {
                    EloquentBuffer::load($model, $method, $field, $context);
                }

                $data = $model->getRelation($method);

                return $data instanceof Collection ? $data->all() : $data;
            };
        }

        return $this->getAttributeValue($model, $field, $context);
    }

    /**
     * @param FlarumContext $context
     */
    public function query(Context $context): object
    {
        $query = $this->newModel($context)->query();

        $this->scope($query, $context);

        return $query;
    }

    /**
     * Hook to scope a query for this resource.
     *
     * @param Builder<M> $query
     * @param FlarumContext $context
     */
    public function scope(Builder $query, Context $context): void
    {
    }

    /**
     * @param Builder<M> $query
     * @param FlarumContext $context
     */
    public function results(object $query, Context $context): iterable
    {
        if ($results = $context->getSearchResults()) {
            return $results->getResults();
        }

        return $query->get();
    }

    /**
     * @param Builder<M> $query
     */
    public function paginate(object $query, OffsetPagination $pagination): void
    {
        $query->take($pagination->limit)->skip($pagination->offset);
    }

    /**
     * @param Builder<M> $query
     * @param FlarumContext $context
     */
    public function count(object $query, Context $context): ?int
    {
        if ($results = $context->getSearchResults()) {
            return $results->getTotalResults();
        }

        return $query->toBase()->getCountForPagination();
    }

    /**
     * @param FlarumContext $context
     */
    public function find(string $id, Context $context): ?object
    {
        return $this->query($context)->find($id);
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @throws \Exception
     */
    public function setValue(object $model, Field $field, mixed $value, Context $context): void
    {
        if ($field instanceof Relationship) {
            $method = $this->method($field);
            $relation = $model->$method();

            // If this is a belongs-to relationship, then the ID is stored on the
            // model itself, so we can set it here.
            if ($relation instanceof BelongsTo) {
                $relation->associate($value);
            }

            return;
        }

        // Mind-blowingly, Laravel discards timezone information when storing
        // dates in the database. Since the API can receive dates in any
        // timezone, we will need to convert it to the app's configured
        // timezone ourselves before storage.
        if (
            $field instanceof Attribute &&
            $field->type instanceof DateTime &&
            $value instanceof \DateTimeInterface
        ) {
            $value = \DateTime::createFromInterface($value)->setTimezone(
                new \DateTimeZone(config('app.timezone')),
            );
        }

        $model->setAttribute($this->property($field), $value);
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function saveValue(object $model, Field $field, mixed $value, Context $context): void
    {
        if ($field instanceof ToMany) {
            $method = $this->method($field);
            $relation = $model->$method();

            if ($relation instanceof BelongsToMany) {
                $relation->sync(new Collection($value));
            }
        }
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function create(object $model, Context $context): object
    {
        $this->saveModel($model, $context);

        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function update(object $model, Context $context): object
    {
        $this->saveModel($model, $context);

        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    protected function saveModel(Model $model, Context $context): void
    {
        $model->save();
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function delete(object $model, Context $context): void
    {
        $model->delete();
    }

    /**
     * Get the model property that a field represents.
     */
    protected function property(Field $field): string
    {
        return $field->property ?: Str::snake($field->name);
    }

    /**
     * Get the model method that a field represents.
     */
    protected function method(Field $field): string
    {
        return $field->property ?: $field->name;
    }

    /** @inheritDoc */
    public function newModel(Context $context): object
    {
        return new ($this->model());
    }

    final public function filters(): array
    {
        throw new RuntimeException('Not supported in Flarum, please use a model searcher instead https://docs.flarum.org/extend/search.');
    }

    /**
     * @param FlarumContext $context
     */
    public function mutateDataBeforeValidation(Context $context, array $data): array
    {
        return $data;
    }
}
