<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Flarum\Ai\AiConfig;
use Flarum\Ai\Usage\Attribution;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Makes AI calls as one feature: through the connection and model the admin
 * chose for it, counted against its limits, and attributed to it and to the
 * member it's for.
 *
 *     $ai->feature('acme-tldr.summaries')->as($actor)->prompt(new Summarise($discussion), 'Summarise this.');
 */
class FeatureCall
{
    protected ?User $actor = null;

    public function __construct(
        protected Feature $feature,
        protected FeatureSettings $settings,
        protected AiConfig $config,
        protected Attribution $attribution
    ) {
    }

    /**
     * The member the call is made for. They need the feature's permission,
     * and the call counts against their daily limit. Without one, the call is
     * the forum's own, such as a job that runs in the background.
     */
    public function as(?User $actor): static
    {
        $call = clone $this;
        $call->actor = $actor;

        return $call;
    }

    /**
     * Whether the feature can be used now, by the member it's for: to decide
     * whether to offer it at all.
     */
    public function available(): bool
    {
        return $this->settings->get($this->feature->key)['enabled']
            && $this->connection() !== null
            && ($this->actor === null || $this->actor->hasPermission($this->feature->permission()));
    }

    /**
     * The connection the feature uses: the one the admin chose for it, or the
     * default connection.
     */
    public function connection(): ?string
    {
        $chosen = $this->settings->get($this->feature->key)['connection'];
        $connections = $this->config->connections();

        if ($chosen !== null && isset($connections[$chosen])) {
            return $chosen;
        }

        return $this->config->defaultConnection();
    }

    /**
     * The model the admin chose for the feature. Without one, the
     * connection's own default model is used.
     */
    public function model(): ?string
    {
        return $this->settings->get($this->feature->key)['model'];
    }

    /**
     * Runs the callback as the feature. It's given the connection and model to
     * use, and every laravel/ai call it makes is counted for the feature.
     *
     * @template T
     * @param callable(string $connection, string|null $model): T $callback
     * @return T
     *
     * @throws FeatureUnavailableException
     * @throws PermissionDeniedException
     */
    public function run(callable $callback): mixed
    {
        if (! $this->settings->get($this->feature->key)['enabled']) {
            throw new FeatureUnavailableException($this->feature->key, 'disabled');
        }

        $connection = $this->connection() ?? throw new FeatureUnavailableException($this->feature->key, 'no_connection');

        if ($this->actor !== null && ! $this->actor->hasPermission($this->feature->permission())) {
            throw new PermissionDeniedException();
        }

        return $this->attribution->during(
            $this->feature->key,
            $this->actor,
            fn () => $callback($connection, $this->model())
        );
    }

    /**
     * Prompts an agent as the feature.
     *
     * @param array<int, mixed> $attachments
     */
    public function prompt(Agent $agent, string $prompt, array $attachments = []): AgentResponse
    {
        return $this->run(fn (string $connection, ?string $model) => $agent->prompt($prompt, $attachments, $connection, $model));
    }
}
