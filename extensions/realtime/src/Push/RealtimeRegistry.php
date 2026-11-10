<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\User\User;

/**
 * Singleton registry for realtime event→broadcast mappings registered by extensions.
 *
 * Extensions register callbacks via the Realtime extender. This registry
 * collects them and EventSubscriber wires them up at boot time.
 */
class RealtimeRegistry
{
    /**
     * Model-based event registrations.
     *
     * Each entry: [
     *   'events'    => string[],
     *   'getModel'  => callable(object): AbstractModel,
     *   'getActor'  => callable(object): ?User,
     *   'eventName' => string|null,   // override broadcast event name; null = FQCN
     * ]
     *
     * @var array<int, array{events: string[], getModel: callable, getActor: callable|null, eventName: string|null}>
     */
    protected array $modelEvents = [];

    /**
     * Dialog message event registrations.
     *
     * Each entry: [
     *   'events'     => string[],
     *   'getMessage' => callable(object): \Flarum\Messages\DialogMessage,
     * ]
     *
     * @var array<int, array{events: string[], getMessage: callable}>
     */
    protected array $dialogEvents = [];

    /**
     * Flag/moderation event registrations (permission-filtered broadcasts).
     *
     * Each entry: [
     *   'events'        => string[],
     *   'getDiscussion' => callable(object): Discussion,
     *   'eventName'     => string,
     * ]
     *
     * @var array<int, array{events: string[], getDiscussion: callable, eventName: string}>
     */
    protected array $flagEvents = [];

    /**
     * Additional model→API-endpoint mappings for the payload Generator.
     *
     * @var array<class-string<AbstractModel>, string>
     */
    protected array $modelEndpoints = [];

    /**
     * Register a model-based event broadcast.
     *
     * @param string[] $events
     * @param callable(object): AbstractModel $getModel
     * @param callable(object): ?User|null $getActor
     */
    public function addModelEvent(array $events, callable $getModel, ?callable $getActor = null, ?string $eventName = null): void
    {
        $this->modelEvents[] = compact('events', 'getModel', 'getActor', 'eventName');
    }

    /**
     * Register a dialog message event broadcast.
     *
     * @param string[] $events
     * @param callable(object): \Flarum\Messages\DialogMessage $getMessage
     */
    public function addDialogEvent(array $events, callable $getMessage): void
    {
        $this->dialogEvents[] = compact('events', 'getMessage');
    }

    /**
     * Register a flag/moderation event broadcast (permission-filtered).
     *
     * @param string[] $events
     * @param callable(object): Discussion $getDiscussion
     */
    public function addFlagEvent(array $events, callable $getDiscussion, string $eventName): void
    {
        $this->flagEvents[] = compact('events', 'getDiscussion', 'eventName');
    }

    /**
     * Register a model class→API endpoint mapping for the payload Generator.
     *
     * @param class-string<AbstractModel> $modelClass
     */
    public function addModelEndpoint(string $modelClass, string $endpoint): void
    {
        $this->modelEndpoints[$modelClass] = $endpoint;
    }

    /** @return array<int, array{events: string[], getModel: callable, getActor: callable|null, eventName: string|null}> */
    public function getModelEvents(): array
    {
        return $this->modelEvents;
    }

    /** @return array<int, array{events: string[], getMessage: callable}> */
    public function getDialogEvents(): array
    {
        return $this->dialogEvents;
    }

    /** @return array<int, array{events: string[], getDiscussion: callable, eventName: string}> */
    public function getFlagEvents(): array
    {
        return $this->flagEvents;
    }

    /** @return array<class-string<AbstractModel>, string> */
    public function getModelEndpoints(): array
    {
        return $this->modelEndpoints;
    }
}
