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
use Flarum\Realtime\Websocket\Api\ChannelRegistry;
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

    protected array $dialogRemovals = [];

    /**
     * @var array<int, array{events: string[], getPayloads: callable}>
     */
    protected array $userBroadcasts = [];

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

    /**
     * @var array<string, array{authorize: callable|string|array, scoped: bool}>
     */
    protected array $privateChannels = [];

    /**
     * @var array<string, array{authorize: callable|string|array, scoped: bool}>
     */
    protected array $presenceChannels = [];

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->afterResolving(Settings::class, function (Settings $settings) {
            $settings->use($this->configuration);
        });

        $container->afterResolving(ChannelRegistry::class, function (ChannelRegistry $registry) use ($container) {
            foreach ($this->privateChannels as $subject => $channel) {
                $registry->addPrivate($subject, $this->resolveChannelCallback($channel['authorize'], $container), $channel['scoped']);
            }

            foreach ($this->presenceChannels as $subject => $channel) {
                $registry->addPresence($subject, $this->resolveChannelCallback($channel['authorize'], $container), $channel['scoped']);
            }
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

            foreach ($this->dialogRemovals as $entry) {
                $registry->addDialogRemoval($entry['events'], $entry['getRemoval']);
            }

            foreach ($this->userBroadcasts as $entry) {
                $registry->addUserBroadcast($entry['events'], $entry['getPayloads']);
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
     * Tell connected dialog participants that a message was deleted for good.
     *
     * Once a message is deleted there is nothing left to render, and deleting a
     * dialog's last message deletes the dialog and its memberships with it. So
     * the `$getRemoval` callback returns what the event recorded beforehand:
     * the message and dialog IDs, whether the dialog was deleted too, and the
     * IDs of the users who could see the message. Those of them who are
     * connected receive `{data: {type: 'dialog-messages', id}, meta: {dialogId,
     * dialogDeleted}}` on their own channel, under the event's class name.
     *
     * Example:
     *
     *   (new \Flarum\Realtime\Extend\Realtime())
     *       ->broadcastDialogRemoval(
     *           \Flarum\Messages\DialogMessage\Event\Deleted::class,
     *           fn ($event) => [
     *               'id' => $event->message->id,
     *               'dialogId' => $event->dialogId,
     *               'dialogDeleted' => $event->dialogDeleted,
     *               'userIds' => $event->memberIds,
     *           ],
     *       ),
     *
     * @param string|string[] $events
     * @param callable(object): array{id: int, dialogId: int, dialogDeleted: bool, userIds: int[]} $getRemoval
     */
    public function broadcastDialogRemoval(string|array $events, callable $getRemoval): self
    {
        $this->dialogRemovals[] = [
            'events' => (array) $events,
            'getRemoval' => $getRemoval,
        ];

        return $this;
    }

    /**
     * Send a payload of your own to particular users, on their own channels,
     * under the event's class name.
     *
     * The callback returns each user's payload keyed by user ID; empty sends
     * nothing. It runs when the event is raised and again when the broadcast
     * is sent (later, with a queue worker), and what it returns then is sent.
     * The event is queued, so give it SerializesModels if it carries models.
     *
     * Realtime checks nothing about these users: the callback must leave out
     * anyone who may not see the payload.
     *
     * Example:
     *
     *   (new \Flarum\Realtime\Extend\Realtime())
     *       ->broadcastToUsers(
     *           \Acme\Event\TaskAssigned::class,
     *           fn ($event) => [$event->assignee->id => ['taskId' => (string) $event->task->id]],
     *       ),
     *
     * @param string|string[] $events
     * @param callable(object): array<int, array<string, mixed>> $getPayloads
     */
    public function broadcastToUsers(string|array $events, callable $getPayloads): self
    {
        $this->userBroadcasts[] = [
            'events' => (array) $events,
            'getPayloads' => $getPayloads,
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
    // Channels
    // -------------------------------------------------------------------------

    /**
     * Register a private channel, and how to decide who may subscribe to it.
     *
     * By default the channel is scoped to one object: it is `private-{subject}={id}`,
     * and the callback is given the actor and that id. Pass `scoped: false` for a
     * forum-wide channel, `private-{subject}` with no id (the callback then gets
     * null). A name of the other shape is refused before the callback runs.
     *
     * Return true to sign the subscription; anything else refuses it.
     *
     * Because the check runs here — in an ordinary request, once per subscription —
     * it has the full permission machinery available, and the websocket server does
     * no permission work per event. That makes the channel a real privilege
     * boundary: an extension can carry data on it that the audience of an existing
     * channel is not entitled to, rather than having to borrow one of realtime's and
     * inherit its audience.
     *
     * Guests reach the callback. Private channels have never required a session (the
     * discussion typing channel is authorized for anyone who can see the discussion,
     * guests included), so a members-only channel must say so itself.
     *
     * Prefix the subject to keep it yours. Registering one twice on the same
     * extender throws straight away; if two extensions claim the same subject, the
     * channel refuses every subscription and the clash is logged, rather than
     * either extension's permissions silently applying to the other's channel.
     *
     * The callback may be a closure, an invokable class name, or
     * `[SomeClass::class, 'method']`; class names are resolved through the
     * container when the channel is first authorized, so they can inject what they
     * need.
     *
     * Example (in an extension's extend.php):
     *
     *   (new Extend\Conditional())
     *       ->whenExtensionEnabled('flarum-realtime', fn () => [
     *           (new \Flarum\Realtime\Extend\Realtime())
     *               ->privateChannel('acme-readers', function (User $actor, int $discussionId) {
     *                   $discussion = Discussion::whereVisibleTo($actor)->find($discussionId);
     *
     *                   return $discussion !== null
     *                       && $actor->can('acme-readers.view', $discussion);
     *               }),
     *       ]),
     *
     * @param string $subject  Channel subject, e.g. 'acme-readers'.
     * @param callable(User $actor, ?int $id): bool|class-string|array{class-string, string} $authorize
     * @param bool $scoped  Whether the channel name carries `={id}`.
     */
    public function privateChannel(string $subject, callable|string|array $authorize, bool $scoped = true): self
    {
        $this->assertNewChannel($this->privateChannels, 'private', $subject);

        $this->privateChannels[$subject] = ['authorize' => $authorize, 'scoped' => $scoped];

        return $this;
    }

    /**
     * Register a presence channel, and how to decide who may subscribe to it.
     *
     * Presence channels keep a member list and emit `member_added`/`member_removed`,
     * so a roster needs no announce/keepalive protocol of its own. Return the member
     * data to publish for this actor — a non-empty array, broadcast to the rest of
     * the channel, so put only what everyone there may see — or false to refuse.
     * An empty array is a refusal too.
     *
     * By default the channel is scoped to one object, `presence-{subject}={id}`, and
     * the id is passed to the callback. Pass `scoped: false` for a forum-wide roster,
     * `presence-{subject}` with no id (the callback then gets null). A name of the
     * other shape is refused before the callback runs, so a forum-wide channel can't
     * be multiplied by a client appending ids to it.
     *
     * Guests are refused before the callback runs: the member list is keyed by user
     * id, so there is nothing to publish for them.
     *
     * Subjects and callbacks follow the same rules as {@link privateChannel()}. To
     * add a further condition to a channel someone else registered, use
     * {@link authorizePresenceChannel()} instead; guards stack, definitions do not.
     *
     * Example:
     *
     *   (new \Flarum\Realtime\Extend\Realtime())
     *       ->presenceChannel('acme-readers', function (User $actor, int $discussionId) {
     *           $discussion = Discussion::whereVisibleTo($actor)->find($discussionId);
     *
     *           if ($discussion === null || ! $actor->can('acme-readers.view', $discussion)) {
     *               return false;
     *           }
     *
     *           return ['displayName' => $actor->display_name];
     *       }),
     *
     * @param string $subject  Channel subject, e.g. 'acme-readers'.
     * @param callable(User $actor, ?int $id): (array<string, mixed>|false|null)|class-string|array{class-string, string} $authorize
     * @param bool $scoped  Whether the channel name carries `={id}`.
     */
    public function presenceChannel(string $subject, callable|string|array $authorize, bool $scoped = true): self
    {
        $this->assertNewChannel($this->presenceChannels, 'presence', $subject);

        $this->presenceChannels[$subject] = ['authorize' => $authorize, 'scoped' => $scoped];

        return $this;
    }

    /**
     * Within one extender a repeated subject is a plain mistake, and catching it
     * here surfaces it while `extend.php` is evaluated — before a second callback,
     * possibly a more permissive one, could quietly replace the first.
     */
    private function assertNewChannel(array $registered, string $type, string $subject): void
    {
        if (isset($registered[$subject])) {
            throw new InvalidArgumentException(
                "The $type channel subject \"$subject\" is already registered on this extender."
            );
        }
    }

    /**
     * Turn an invokable class name or a `[class-string, method]` pair into a
     * callable, resolving the class through the container only when the channel is
     * first authorized.
     */
    private function resolveChannelCallback(callable|string|array $callback, Container $container): callable
    {
        if (is_array($callback) && is_string($callback[0] ?? null) && ! is_callable($callback)) {
            [$class, $method] = $callback;

            return function (...$args) use ($container, $class, $method) {
                return $container->make($class)->$method(...$args);
            };
        }

        if (is_string($callback) && ! is_callable($callback)) {
            return function (...$args) use ($container, $callback) {
                return $container->make($callback)(...$args);
            };
        }

        if (! is_callable($callback)) {
            throw new InvalidArgumentException('A channel callback must be callable, an invokable class name, or a [class, method] pair.');
        }

        return $callback;
    }

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
