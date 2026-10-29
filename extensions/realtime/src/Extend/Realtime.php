<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Extend;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Realtime\Websocket\Api\PresenceChannelAuthorizer;
use Flarum\Realtime\Websocket\Settings;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class Realtime implements ExtenderInterface
{
    protected array $configuration = [];

    /**
     * @var array<int, array{events: string[], getModel: callable, getActor: callable|null, eventName: string|null}>
     */
    protected array $modelEvents = [];

    /**
     * @var array<int, array{events: string[], getMessage: callable}>
     */
    protected array $dialogEvents = [];

    /**
     * @var array<int, array{events: string[], getDiscussion: callable, eventName: string}>
     */
    protected array $flagEvents = [];

    /**
     * @var array<class-string<AbstractModel>, string>
     */
    protected array $modelEndpoints = [];

    /**
     * @var array<string, callable[]>
     */
    protected array $presenceChannelGuards = [];

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->afterResolving(Settings::class, function (Settings $settings) {
            $settings->use($this->configuration);
        });

        $container->afterResolving(PresenceChannelAuthorizer::class, function (PresenceChannelAuthorizer $authorizer) {
            foreach ($this->presenceChannelGuards as $channel => $callbacks) {
                foreach ($callbacks as $callback) {
                    $authorizer->add($channel, $callback);
                }
            }
        });

        $container->afterResolving(RealtimeRegistry::class, function (RealtimeRegistry $registry) {
            foreach ($this->modelEvents as $entry) {
                $registry->addModelEvent($entry['events'], $entry['getModel'], $entry['getActor'], $entry['eventName']);
            }

            foreach ($this->dialogEvents as $entry) {
                $registry->addDialogEvent($entry['events'], $entry['getMessage']);
            }

            foreach ($this->flagEvents as $entry) {
                $registry->addFlagEvent($entry['events'], $entry['getDiscussion'], $entry['eventName']);
            }

            foreach ($this->modelEndpoints as $modelClass => $endpoint) {
                $registry->addModelEndpoint($modelClass, $endpoint);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Server configuration
    // -------------------------------------------------------------------------

    /**
     * Provide the full url to the running `php flarum realtime:serve` daemon.
     * In case you proxy it, use the outside URL.
     *
     * @example https://wss.flarum.site
     * @example https://flarum.site:9001
     *
     * @see `php flarum realtime:serve --help`
     */
    public function daemonUrl(string $url): self
    {
        $parsed = parse_url($url);

        if (! empty($parsed['path'])) {
            throw new InvalidArgumentException('Paths are not possible in websocket connections.');
        }

        $this->configuration['php-client-secure'] = ($parsed['scheme'] ?? 'http') === 'https';
        $this->configuration['php-client-host'] = $parsed['host'] ?? '';
        $this->configuration['php-client-port'] = $parsed['port'] ?? null;

        $this->configuration['js-client-secure'] = ($parsed['scheme'] ?? 'http') === 'https';
        $this->configuration['js-client-host'] = $parsed['host'] ?? '';
        $this->configuration['js-client-port'] = $parsed['port'] ?? null;

        return $this;
    }

    /**
     * Set maximum number of allowed websocket connections.
     */
    public function maxConnections(int $connections): self
    {
        $this->configuration['max-connections'] = $connections;

        return $this;
    }

    /**
     * Set the Pusher app key and secret used for channel authentication.
     * The key is sent to clients; the secret is used server-side only.
     */
    public function app(string $key, string $secret): self
    {
        $this->configuration['app-key'] = $key;
        $this->configuration['app-secret'] = $secret;

        return $this;
    }

    /**
     * Override arbitrary settings values.
     */
    public function use(array $settings): self
    {
        $this->configuration = array_merge($this->configuration, $settings);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Event broadcast registration
    // -------------------------------------------------------------------------

    /**
     * Broadcast a model-based event to connected users.
     *
     * The `$getModel` callback receives the event object and must return the
     * Eloquent model to broadcast (Post, Discussion, User, etc.). Realtime uses
     * the model to resolve which users should receive the payload and to make
     * the internal API request that generates the personalised JSON:API payload.
     *
     * The optional `$getActor` callback returns the User who caused the event.
     * That user is excluded from receiving the broadcast (they already know what
     * happened — they caused it).
     *
     * The optional `$eventName` overrides the Pusher channel event name. When
     * omitted the fully-qualified PHP class name of the event is used.
     *
     * Example (in an extension's extend.php):
     *
     *   (new Extend\Conditional())
     *       ->whenExtensionEnabled('flarum-realtime', fn () => [
     *           (new \Flarum\Realtime\Extend\Realtime())
     *               ->broadcastModelEvent(
     *                   [\Flarum\Likes\Event\PostWasLiked::class, \Flarum\Likes\Event\PostWasUnliked::class],
     *                   fn ($event) => $event->post,
     *                   fn ($event) => $event->user,
     *                   'likesMutation'
     *               ),
     *       ]),
     *
     * @param string|string[] $events  One or more fully-qualified event class names.
     * @param callable(object): AbstractModel $getModel
     * @param callable(object): ?User|null $getActor
     * @param string|null $eventName  Pusher event name sent to JS clients.
     */
    public function broadcastModelEvent(
        string|array $events,
        callable $getModel,
        ?callable $getActor = null,
        ?string $eventName = null
    ): self {
        $this->modelEvents[] = [
            'events' => (array) $events,
            'getModel' => $getModel,
            'getActor' => $getActor,
            'eventName' => $eventName,
        ];

        return $this;
    }

    /**
     * Broadcast a private dialog message event to connected dialog participants.
     *
     * The `$getMessage` callback receives the event object and must return the
     * DialogMessage model. Realtime sends the payload only to users who are
     * members of the dialog and are currently connected.
     *
     * Example:
     *
     *   (new \Flarum\Realtime\Extend\Realtime())
     *       ->broadcastDialogEvent(
     *           \Flarum\Messages\DialogMessage\Event\Created::class,
     *           fn ($event) => $event->message,
     *       )
     *       ->registerModelEndpoint(\Flarum\Messages\DialogMessage::class, 'dialog-messages')
     *       ->registerModelEndpoint(\Flarum\Messages\Dialog::class, 'dialogs'),
     *
     * @param string|string[] $events
     * @param callable(object): \Flarum\Messages\DialogMessage $getMessage
     */
    public function broadcastDialogEvent(string|array $events, callable $getMessage): self
    {
        $this->dialogEvents[] = [
            'events' => (array) $events,
            'getMessage' => $getMessage,
        ];

        return $this;
    }

    /**
     * Broadcast a flag/moderation event only to users who have permission to
     * view flags on the relevant discussion.
     *
     * The `$getDiscussion` callback receives the event object and must return
     * the Discussion the flag belongs to. Only connected users with the
     * `discussion.viewFlags` permission receive the broadcast.
     *
     * Example:
     *
     *   (new \Flarum\Realtime\Extend\Realtime())
     *       ->broadcastFlagEvent(
     *           [\Flarum\Flags\Event\Created::class, \Flarum\Flags\Event\Deleting::class],
     *           fn ($event) => $event->flag->post->discussion,
     *           'flagged'
     *       ),
     *
     * @param string|string[] $events
     * @param callable(object): Discussion $getDiscussion
     * @param string $eventName  Pusher event name sent to JS clients.
     */
    public function broadcastFlagEvent(
        string|array $events,
        callable $getDiscussion,
        string $eventName
    ): self {
        $this->flagEvents[] = [
            'events' => (array) $events,
            'getDiscussion' => $getDiscussion,
            'eventName' => $eventName,
        ];

        return $this;
    }

    /**
     * Register an Eloquent model class → JSON:API endpoint mapping.
     *
     * The payload Generator uses this to make internal API requests when
     * building the personalised payload to broadcast. Core models (Discussion,
     * Post, User) are registered by default. Extensions that broadcast
     * additional model types (e.g. DialogMessage) must register them here.
     *
     * @param class-string<AbstractModel> $modelClass
     * @param string $endpoint  The API endpoint path segment, e.g. 'dialog-messages'.
     */
    public function registerModelEndpoint(string $modelClass, string $endpoint): self
    {
        $this->modelEndpoints[$modelClass] = $endpoint;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Presence channel authorization
    // -------------------------------------------------------------------------

    /**
     * Register a callback to authorize access to a presence channel.
     *
     * The callback receives the actor and the channel subject name (the part
     * after `presence-`, e.g. `'online'` for `presence-online`). Return
     * `false` to deny; any other return value (including `null`) is treated as
     * a pass. All registered callbacks for the channel must pass before the
     * authentication response is issued.
     *
     * The actor is guaranteed to be a logged-in user — guests are rejected
     * before callbacks are invoked.
     *
     * Example (in an extension's extend.php):
     *
     *   (new Extend\Conditional())
     *       ->whenExtensionEnabled('flarum-realtime', fn () => [
     *           (new \Flarum\Realtime\Extend\Realtime())
     *               ->authorizePresenceChannel('online',
     *                   fn (User $actor) => $actor->hasPermission('viewOnlineUsersWidget')),
     *       ]),
     *
     * @param string $channel  Channel subject name, e.g. 'online'.
     * @param callable(User $actor, string $channel): bool $callback
     */
    public function authorizePresenceChannel(string $channel, callable $callback): self
    {
        $this->presenceChannelGuards[$channel][] = $callback;

        return $this;
    }
}
