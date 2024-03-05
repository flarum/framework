<?php

namespace Flarum\Api\Resource;

use Flarum\Api\Resource\Contracts\{Findable,
    Listable,
    Countable,
    Paginatable,
    Creatable,
    Resource,
    Updatable,
    Deletable};
use Flarum\Api\Resource\Concerns\Bootable;
use Flarum\Api\Resource\Concerns\Extendable;
use Flarum\Api\Resource\Concerns\HasSortMap;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\User\User;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\EloquentResource as BaseResource;

abstract class AbstractDatabaseResource extends BaseResource implements
    Resource,
    Findable,
    Listable,
    Countable,
    Paginatable,
    Creatable,
    Updatable,
    Deletable
{
    use Bootable;
    use Extendable;
    use HasSortMap;
    use DispatchEventsTrait {
        dispatchEventsFor as traitDispatchEventsFor;
    }

    abstract public function model(): string;

    public function newModel(Context $context): object
    {
        return new ($this->model());
    }

    public function resource(object $model, Context $context): ?string
    {
        $baseModel = $this->model();

        if ($model instanceof $baseModel) {
            return $this->type();
        }

        return null;
    }

    public function filters(): array
    {
        throw new RuntimeException('Not supported in Flarum, please use a model searcher instead https://docs.flarum.org/extend/search.');
    }

    public function createAction(object $model, Context $context): object
    {
        $model = parent::createAction($model, $context);

        $this->dispatchEventsFor($model, $context->getActor());

        return $model;
    }

    public function updateAction(object $model, Context $context): object
    {
        $model = parent::updateAction($model, $context);

        $this->dispatchEventsFor($model, $context->getActor());

        return $model;
    }

    public function deleteAction(object $model, Context $context): void
    {
        $this->deleting($model, $context);

        $this->delete($model, $context);

        $this->deleted($model, $context);

        $this->dispatchEventsFor($model, $context->getActor());
    }

    public function creating(object $model, Context $context): ?object
    {
        return $model;
    }

    public function updating(object $model, Context $context): ?object
    {
        return $model;
    }

    public function saving(object $model, Context $context): ?object
    {
        return $model;
    }

    public function saved(object $model, Context $context): ?object
    {
        return $model;
    }

    public function created(object $model, Context $context): ?object
    {
        return $model;
    }

    public function updated(object $model, Context $context): ?object
    {
        return $model;
    }

    public function deleting(object $model, Context $context): void
    {
        //
    }

    public function deleted(object $model, Context $context): void
    {
        //
    }

    public function dispatchEventsFor(mixed $entity, User $actor = null): void
    {
        if (method_exists($entity, 'releaseEvents')) {
            $this->traitDispatchEventsFor($entity, $actor);
        }
    }

    public function mutateDataBeforeValidation(Context $context, array $data): array
    {
        return $data;
    }

    public function results(object $query, Context $context): iterable
    {
        if ($results = $context->getSearchResults()) {
            return $results->getResults();
        }

        return $query->get();
    }

    public function count(object $query, Context $context): ?int
    {
        if ($results = $context->getSearchResults()) {
            return $results->getTotalResults();
        }

        return parent::count($query, $context);
    }
}
