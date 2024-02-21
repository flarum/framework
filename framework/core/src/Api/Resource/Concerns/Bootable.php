<?php

namespace Flarum\Api\Resource\Concerns;

use Flarum\Api\JsonApi;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;

trait Bootable
{
    protected readonly JsonApi $api;
    protected readonly Dispatcher $events;
    protected readonly Factory $validation;

    /**
     * Avoids polluting the constructor of the resource with dependencies.
     */
    public function boot(JsonApi $api): static
    {
        $this->api = $api;
        $this->events = $api->getContainer()->make(Dispatcher::class);
        $this->validation = $api->getContainer()->make(Factory::class);

        return $this;
    }

    /**
     * Called by the JSON:API server package to resolve the validation factory.
     */
    public function validationFactory(): Factory
    {
        return $this->validation;
    }
}
