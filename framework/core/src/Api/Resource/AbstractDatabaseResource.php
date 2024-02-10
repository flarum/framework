<?php

namespace Flarum\Api\Resource;

use Flarum\Api\Resource\Contracts\{
    Findable,
    Listable,
    Countable,
    Paginatable,
    Creatable,
    Updatable,
    Deletable
};
use Flarum\Api\Resource\Concerns\Bootable;
use Flarum\Foundation\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\EloquentResource as BaseResource;

abstract class AbstractDatabaseResource extends BaseResource implements
    Findable,
    Listable,
    Countable,
    Paginatable,
    Creatable,
    Updatable,
    Deletable
{
    use Bootable;
    use DispatchEventsTrait;

    abstract public function model(): string;

    public function newModel(Context $context): object
    {
        return new ($this->model());
    }

    public function filters(): array
    {
        throw new \RuntimeException('Not supported in Flarum, please use a model searcher instead https://docs.flarum.org/extend/search.');
    }

    public function create(object $model, Context $context): object
    {
        $model = parent::create($model, $context);

        $this->dispatchEventsFor($model, $context->getActor());

        return $model;
    }

    public function update(object $model, Context $context): object
    {
        $model = parent::update($model, $context);

        $this->dispatchEventsFor($model, $context->getActor());

        return $model;
    }

    public function delete(object $model, Context $context): void
    {
        $this->deleting($model, $context);

        parent::delete($model, $context);

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

    protected function bcSavingEvent(Context $context, array $data): ?object
    {
        return null;
    }

    public function mutateDataBeforeValidation(Context $context, array $data, bool $validateAll): array
    {
        return $data;

        // @todo: decided to completely drop this.
        $savingEvent = $this->bcSavingEvent($context, $data);

        if ($savingEvent) {
            // BC Layer for Flarum 1.0
            // @todo: should we drop this or keep it for 2.0? another massive BC break.
            // @todo: replace with resource extenders
            $this->container->make(Dispatcher::class)->dispatch(
                $savingEvent
            );

            return array_merge($data, $context->model->getDirty());
        }

        return $data;
    }

    public function results(object $query, Context $context): array
    {
        if ($results = $context->getSearchResults()) {
            return $results->getResults()->all();
        }

        return parent::results($query, $context);
    }

    public function count(object $query, Context $context): ?int
    {
        if ($results = $context->getSearchResults()) {
            return $results->getTotalResults();
        }

        return parent::count($query, $context);
    }
}
