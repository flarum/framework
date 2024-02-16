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
use Flarum\Api\Resource\Concerns\ResolvesValidationFactory;
use Flarum\Foundation\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use RuntimeException;
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
    use ResolvesValidationFactory;

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

    protected function newSavingEvent(Context $context, array $data): ?object
    {
        return null;
    }

    public function mutateDataBeforeValidation(Context $context, array $data, bool $validateAll): array
    {
        $dirty = $context->model->getDirty();

        $savingEvent = $this->newSavingEvent($context, Arr::get($context->body(), 'data', []));

        if ($savingEvent) {
            $this->container->make(Dispatcher::class)->dispatch($savingEvent);

            $dirtyAfterEvent = $context->model->getDirty();

            // Unlike 1.0, the saving events in 2.0 do not allow modifying the model.
            if ($dirtyAfterEvent !== $dirty) {
                throw new RuntimeException('You should modify the model through the saving event. Please use the resource extenders instead.');
            }
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
