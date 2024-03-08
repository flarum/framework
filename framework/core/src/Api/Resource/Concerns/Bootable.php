<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource\Concerns;

use Flarum\Api\JsonApi;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;

trait Bootable
{
    protected JsonApi $api;
    protected Dispatcher $events;
    protected Factory $validation;

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
