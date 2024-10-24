<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource\Concerns;

use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Resource\Contracts\Creatable;
use Flarum\Api\Resource\Contracts\Deletable;
use Flarum\Api\Resource\Contracts\Updatable;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\User\User;
use RuntimeException;
use Tobyz\JsonApiServer\Context;

/**
 * @template M of object
 */
trait HasHooks
{
    use DispatchEventsTrait {
        dispatchEventsFor as traitDispatchEventsFor;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function createAction(object $model, Context $context): object
    {
        if (! $this instanceof Creatable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($this), Creatable::class),
            );
        }

        $model = $this->creating($model, $context) ?: $model;

        $model = $this->saving($model, $context) ?: $model;

        $model = $this->create($model, $context);

        $model = $this->saved($model, $context) ?: $model;

        $model = $this->created($model, $context) ?: $model;

        $this->dispatchEventsFor($model, $context->getActor());

        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function updateAction(object $model, Context $context): object
    {
        if (! $this instanceof Updatable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($this), Updatable::class),
            );
        }

        $model = $this->updating($model, $context) ?: $model;

        $model = $this->saving($model, $context) ?: $model;

        $this->update($model, $context);

        $model = $this->saved($model, $context) ?: $model;

        $model = $this->updated($model, $context) ?: $model;

        $this->dispatchEventsFor($model, $context->getActor());

        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function deleteAction(object $model, Context $context): void
    {
        if (! $this instanceof Deletable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($this), Deletable::class),
            );
        }

        $this->deleting($model, $context);

        $this->delete($model, $context);

        $this->deleted($model, $context);

        $this->dispatchEventsFor($model, $context->getActor());
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @return M|null
     */
    public function creating(object $model, Context $context): ?object
    {
        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @return M|null
     */
    public function updating(object $model, Context $context): ?object
    {
        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @return M|null
     */
    public function saving(object $model, Context $context): ?object
    {
        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @return M|null
     */
    public function saved(object $model, Context $context): ?object
    {
        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @return M|null
     */
    public function created(object $model, Context $context): ?object
    {
        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     * @return M|null
     */
    public function updated(object $model, Context $context): ?object
    {
        return $model;
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
    public function deleting(object $model, Context $context): void
    {
        //
    }

    /**
     * @param M $model
     * @param FlarumContext $context
     */
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
}
