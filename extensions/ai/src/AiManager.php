<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Flarum\Ai\Bedrock\GuardrailGateway;
use Flarum\Ai\Exception\NotConfiguredException;
use Flarum\Ai\Features\FeatureGateway;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Ai\Models\ModelHealth;
use Flarum\Ai\Models\TemperatureSupport;
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
 * notes the model the feature used ({@see FeatureGateway}). Every call through
 * a Bedrock connection with a guardrail is guarded by it.
 *
 * @internal
 */
class AiManager extends BaseAiManager
{
    public function getInstanceConfig($name): array
    {
        $config = parent::getInstanceConfig($name);

        $aiConfig = $this->app->make(AiConfig::class);

        if (! isset($config['key']) && $aiConfig->isAdminConnection((string) $name)) {
            // Set even when there's none: providers that work without a key
            // still read it.
            $config['key'] = $this->app->make(ConnectionKeys::class)->get((string) $name);

            // Given no usable key, the AWS SDK reaches for the server's own
            // credentials, and its Bedrock API key in AWS_BEARER_TOKEN_BEDROCK,
            // which only config.php can lend to the admin's connections. A key
            // laravel/ai would drop, such as "0", is no key.
            if (($config['driver'] ?? null) === 'bedrock' && ! $aiConfig->bedrockServerCredentials()) {
                if (empty($config['key'])) {
                    throw NotConfiguredException::bedrockKey((string) $name);
                }

                $config['use_default_credential_provider'] = false;
            }
        }

        return $config;
    }

    public function textProviderFor(Agent $agent, ?string $name = null): TextProvider
    {
        $provider = $this->guarded(parent::textProviderFor($agent, $name));
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
            fn (string $model) => $health->observe($context->feature, $provider->name(), $model, $defaultModel()),
            $provider->name(),
            $this->app->make(TemperatureSupport::class),
            $context->feature
        ));
    }

    /**
     * A Bedrock connection's provider with the connection's guardrail on every
     * call, if it has one ({@see GuardrailGateway}).
     */
    protected function guarded(TextProvider $provider): TextProvider
    {
        $connection = $this->app->make(AiConfig::class)->connections()[$provider->name()] ?? null;
        $guardrail = $connection['guardrail_id'] ?? null;

        if (($connection['driver'] ?? null) !== 'bedrock' || ! is_string($guardrail) || $guardrail === '' || ! method_exists($provider, 'textGateway')) {
            return $provider;
        }

        $version = $connection['guardrail_version'] ?? 'DRAFT';

        return (clone $provider)->useTextGateway(new GuardrailGateway(
            $provider->textGateway(),
            $guardrail,
            is_string($version) || is_int($version) ? (string) $version : 'DRAFT'
        ));
    }
}
