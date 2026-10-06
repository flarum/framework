<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Laravel\Ai\AiManager;

/**
 * Writes the current connections into laravel/ai's config. It runs when the
 * app boots, and again when the admin changes AI settings.
 */
class ConfigureAi
{
    public function __construct(
        protected Container $container,
        protected AiConfig $config
    ) {
    }

    public function __invoke(): void
    {
        /** @var Repository $repository */
        $repository = $this->container->make('config');

        $previous = array_keys((array) $repository->get('ai.providers', []));

        $repository->set('ai', $this->config->toArray());

        // The manager caches each connection it builds, so one built before the
        // change would keep its old key and URL for the rest of the process.
        if ($this->container->resolved(AiManager::class)) {
            $this->container->make(AiManager::class)->forgetInstance(
                array_values(array_unique([...$previous, ...array_keys($repository->get('ai.providers'))]))
            );
        }
    }
}
