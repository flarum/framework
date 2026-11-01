<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Flarum\Ai\AiConfig;
use Flarum\Ai\Exception\FeatureDisabledException;
use Flarum\Ai\Exception\LimitReachedException;
use Flarum\Ai\Exception\NotConfiguredException;
use Flarum\Ai\Usage\Attribution;
use Flarum\Ai\Usage\Context;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use LogicException;

/**
 * Makes AI calls as one feature: through the connection and model the admin
 * chose for it, counted against its limits, and recorded for it and for who
 * it was made for. Get one from {@see \Flarum\Ai\Ai::feature()}.
 *
 * Every call says who it's for, and is refused until it does:
 *
 *     $ai->feature('acme-tldr.summaries')->as($actor)->prompt(new Summarise($discussion), 'Summarise this.');
 *     $ai->feature('acme-tldr.digest')->asSystem()->prompt(new Digest(), 'Write this week\'s digest.');
 *
 * A call that can't be made throws an {@see \Flarum\Ai\Exception\UnavailableException}
 * before anything is sent: the feature is off, there's no connection, or a
 * limit is used up. A member or guest without the feature's permission gets a
 * PermissionDeniedException. Check {@see available()} first to decide whether
 * to offer the feature at all.
 *
 * In tests, laravel/ai's fakes work as usual (`YourAgent::fake([...])`), and
 * {@see \Flarum\Ai\Testing\InteractsWithAi} sets up a connection and turns the
 * feature on.
 */
class FeatureCall
{
    protected ?User $actor = null;

    protected bool $system = false;

    public function __construct(
        protected Feature $feature,
        protected FeatureSettings $settings,
        protected AiConfig $config,
        protected Attribution $attribution,
        protected Queue $queue
    ) {
    }

    /**
     * Makes the call for a member or guest. They need the feature's permission,
     * and the call counts against their daily limit, or the guests' shared one.
     */
    public function as(User $actor): static
    {
        $call = clone $this;
        $call->actor = $actor;
        $call->system = false;

        return $call;
    }

    /**
     * Makes the call for the forum itself, such as a scheduled job. No
     * permission is needed, and only the feature's and connection's limits apply.
     */
    public function asSystem(): static
    {
        $call = clone $this;
        $call->actor = null;
        $call->system = true;

        return $call;
    }

    /**
     * Whether the call could be made now: the feature is on, it has a
     * connection, and whoever it's for has its permission. Limits aren't
     * checked: a call can still be stopped by one.
     */
    public function available(): bool
    {
        $this->context();

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

        if ($chosen !== null && isset($this->config->connections()[$chosen])) {
            return $chosen;
        }

        return $this->config->defaultConnection();
    }

    /**
     * The model the admin chose for the feature. Without one, the connection's
     * own default model is used.
     */
    public function model(): ?string
    {
        return $this->settings->get($this->feature->key)['model'];
    }

    /**
     * Runs the callback as the feature. It's given the connection and model to
     * use, and every laravel/ai call it makes while it runs is counted for the
     * feature, such as embeddings or images. Prefer {@see prompt()},
     * {@see stream()} and {@see queue()} for agents.
     *
     * @template T
     * @param callable(string $connection, string|null $model): T $callback
     * @return T
     *
     * @throws FeatureDisabledException|NotConfiguredException|LimitReachedException|PermissionDeniedException
     */
    public function run(callable $callback): mixed
    {
        $connection = $this->assertCanCall();

        return $this->attribution->during($this->context(), fn () => $callback($connection, $this->model()));
    }

    /**
     * Prompts an agent as the feature.
     *
     * @param array<int, mixed> $attachments
     *
     * @throws FeatureDisabledException|NotConfiguredException|LimitReachedException|PermissionDeniedException
     */
    public function prompt(Agent $agent, string $prompt, array $attachments = [], ?int $timeout = null): AgentResponse
    {
        $connection = $this->assertCanCall();
        $this->attribution->bind($agent, $this->context());

        return $this->attribution->during(
            $this->context(),
            fn () => $agent->prompt($prompt, $attachments, $connection, $this->model(), $timeout)
        );
    }

    /**
     * Streams an agent's reply as the feature. The stream is counted for the
     * feature however long after this returns it's read. Limits are checked
     * when reading starts, so a LimitReachedException can be thrown then.
     *
     * @param array<int, mixed> $attachments
     *
     * @throws FeatureDisabledException|NotConfiguredException|PermissionDeniedException
     */
    public function stream(Agent $agent, string $prompt, array $attachments = [], ?int $timeout = null): StreamableAgentResponse
    {
        $connection = $this->assertCanCall();
        $this->attribution->bind($agent, $this->context());

        return $agent->stream($prompt, $attachments, $connection, $this->model(), $timeout);
    }

    /**
     * Prompts an agent as the feature on the queue. The agent and attachments
     * are serialized, so they must be serializable, and the agent should act
     * on its reply itself, for example by saving it.
     *
     * The feature, permission and connection are checked now, and again when
     * the job runs; limits only when it runs. Use this rather than the agent's
     * own queue(), which doesn't know which feature the call is for.
     *
     * @param array<int, mixed> $attachments
     *
     * @throws FeatureDisabledException|NotConfiguredException|PermissionDeniedException
     */
    public function queue(Agent $agent, string $prompt, array $attachments = []): void
    {
        $this->assertCanCall();

        $this->queue->push(new RunQueuedFeatureCall(
            $this->feature->key,
            $this->system ? null : $this->actor,
            $agent,
            $prompt,
            $attachments
        ));
    }

    /**
     * @throws FeatureDisabledException|NotConfiguredException|PermissionDeniedException
     */
    protected function assertCanCall(): string
    {
        $context = $this->context();

        if (! $this->settings->get($this->feature->key)['enabled']) {
            throw new FeatureDisabledException($this->feature->key);
        }

        $connection = $this->connection() ?? throw new NotConfiguredException($this->feature->key);

        if ($context->actor !== null && ! $context->actor->hasPermission($this->feature->permission())) {
            throw new PermissionDeniedException();
        }

        return $connection;
    }

    /**
     * @throws LogicException when the call hasn't said who it's for
     */
    protected function context(): Context
    {
        if (! $this->system && $this->actor === null) {
            throw new LogicException("Say who the AI feature \"{$this->feature->key}\" is called for: ->as(\$actor), or ->asSystem() for the forum's own work.");
        }

        return new Context($this->feature->key, $this->actor);
    }
}
