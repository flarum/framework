<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Flarum\Ai\Unconfigured\UnconfiguredProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Laravel\Ai\AiManager;
use Laravel\Ai\AiServiceProvider as LaravelAiServiceProvider;

/**
 * The Laravel AI SDK's own provider, without the parts that need
 * laravel/framework: its config file, which reads env(), and the publishing
 * and make:* commands it registers whenever it runs in a console, including
 * queue workers.
 *
 * Its conversation store isn't bound. Its tables need a Flarum migration,
 * which comes with conversation support.
 */
class AiServiceProvider extends LaravelAiServiceProvider
{
    public function register(): void
    {
        // laravel/ai calls through facades (Ai, Http, Event), which Flarum never
        // roots. They're also cleared, so none hands back an instance from an
        // earlier app: tests boot one per test.
        Facade::setFacadeApplication($this->app->make(Application::class));
        Facade::clearResolvedInstances();

        $this->app->singleton(AiManager::class, fn (Application $app) => (new AiManager($app))->extend(
            AiConfig::UNCONFIGURED,
            fn (Application $app, array $config) => new UnconfiguredProvider($config, $app->make(Dispatcher::class))
        ));
    }

    public function boot(): void
    {
        parent::boot();

        // Configured at boot rather than when the manager is resolved: agents
        // read `ai.default` straight from config to choose their provider,
        // before they ever touch the manager.
        $this->app->make(ConfigureAi::class)();
    }

    protected function registerCommands(): void
    {
    }

    protected function registerPublishing(): void
    {
    }
}
