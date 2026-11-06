<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Flarum\Ai\Features\FeatureGateway;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Ai\Models\ModelHealth;
use Flarum\Ai\Usage\Attribution;
use Laravel\Ai\AiManager as BaseAiManager;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Throwable;

/**
 * laravel/ai's manager, given the API key of a connection set up on the admin
 * page only when that connection's provider is first used. The rest of the
 * time the key stays in its table: it isn't in the config built on every
 * request.
 *
 * An agent prompted as a feature gets a provider that sends the temperature
 * and longest reply the admin set for that feature, falls back to the
 * connection's default model if the one chosen for the feature is gone, and
 * notes the model the feature used ({@see FeatureGateway}).
 *
 * @internal
 */
class AiManager extends BaseAiManager
{
    public function getInstanceConfig($name): array
    {
        $config = parent::getInstanceConfig($name);

        if (! isset($config['key']) && $this->app->make(AiConfig::class)->isAdminConnection((string) $name)) {
            // Set even when there's none: providers that work without a key
            // still read it.
            $config['key'] = $this->app->make(ConnectionKeys::class)->get((string) $name);
        }

        return $config;
    }

    public function textProviderFor(Agent $agent, ?string $name = null): TextProvider
    {
        $provider = parent::textProviderFor($agent, $name);
        $context = $this->app->make(Attribution::class)->for($agent);

        if ($context === null) {
            return $provider;
        }

        $settings = $this->app->make(FeatureSettings::class)->get($context->feature);

        // Every laravel/ai provider has a text gateway to wrap, but the contract
        // doesn't say so: one from elsewhere without it is used as it is.
        if (! method_exists($provider, 'textGateway')) {
            return $provider;
        }

        $health = $this->app->make(ModelHealth::class);

        $defaultModel = function () use ($provider): ?string {
            try {
                return $provider->defaultTextModel();
            } catch (Throwable) {
                // Some providers have no default unless one is set.
                return null;
            }
        };

        // A copy, so the provider other calls share keeps its own gateway.
        return (clone $provider)->useTextGateway(new FeatureGateway(
            $provider->textGateway(),
            $settings['temperature'],
            $settings['max_tokens'],
            $settings['model'],
            $defaultModel,
            fn (string $model) => $health->recordFailure($provider->name(), $model),
            fn (string $model) => $health->observe($context->feature, $provider->name(), $model, $defaultModel())
        ));
    }
}
