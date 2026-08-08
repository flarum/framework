<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\User\User;
use Psr\Log\LoggerInterface;

/**
 * The set of channels clients may subscribe to, and how each one is authorized.
 *
 * A channel name is `{private|presence}-{subject}[={id}]`; the subject selects an
 * entry here and the callback decides whether this actor may join. Everything
 * realtime itself offers is registered like anything else (see the extension's
 * `extend.php`), so there is one code path rather than a built-in set plus an
 * extension set.
 *
 * Each channel is registered either *scoped* — it addresses one object, and its
 * name must carry `={id}` — or *forum-wide*, in which case its name must not.
 * A name of the wrong shape is refused before the callback runs, so a forum-wide
 * channel cannot be multiplied into `presence-online=1`, `presence-online=2`, …
 * by a client, and a scoped callback never has to handle a missing id.
 *
 * Authorizing here rather than in the websocket server is deliberate: this runs
 * in an ordinary HTTP request, once per subscription, with a real actor and the
 * full permission machinery available. The long-lived server process has none of
 * that, and doing it per event would put a permission check on the hot path.
 *
 * This is only the *definition* of a channel. {@link PresenceChannelAuthorizer}
 * is the complementary hook for adding further guards to a presence channel
 * someone else defined; guards stack, definitions do not.
 */
class ChannelRegistry
{
    public const PRIVATE = 'private';
    public const PRESENCE = 'presence';

    /**
     * @var array<string, array<string, array{scoped: bool, authorize: callable}>>
     */
    private array $channels = [self::PRIVATE => [], self::PRESENCE => []];

    /**
     * Subjects that more than one registration claimed. They are refused outright
     * rather than given to either claimant.
     *
     * @var array<string, array<string, true>>
     */
    private array $conflicted = [self::PRIVATE => [], self::PRESENCE => []];

    public function __construct(
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @param callable(User $actor, ?int $id): bool $authorize
     */
    public function addPrivate(string $subject, callable $authorize, bool $scoped = true): void
    {
        $this->add(self::PRIVATE, $subject, $authorize, $scoped);
    }

    /**
     * @param callable(User $actor, ?int $id): (array<string, mixed>|false|null) $authorize
     */
    public function addPresence(string $subject, callable $authorize, bool $scoped = true): void
    {
        $this->add(self::PRESENCE, $subject, $authorize, $scoped);
    }

    /**
     * Whether `{type}-{subject}[={id}]` names a channel that exists: the subject is
     * registered (and not conflicted), and the id is present exactly when the
     * channel is scoped.
     *
     * Checked before anything else looks at the request, so guards and callbacks
     * only ever run for real channel names.
     */
    public function accepts(string $type, string $subject, ?int $id): bool
    {
        $channel = $this->channels[$type][$subject] ?? null;

        return $channel !== null
            && ! isset($this->conflicted[$type][$subject])
            && $channel['scoped'] === ($id !== null);
    }

    /**
     * Whether the actor may subscribe to `private-{subject}[={id}]`.
     *
     * An unregistered subject, a conflicted one and a wrongly-shaped name are all
     * indistinguishable from a refusal — a caller cannot probe for which channels
     * exist.
     *
     * Note that guests reach these callbacks: private channels have never required
     * a session (the discussion typing channel is authorized for anyone who can
     * see the discussion, guests included). A channel that should be members-only
     * has to say so itself.
     */
    public function authorizePrivate(string $subject, User $actor, ?int $id): bool
    {
        if (! $this->accepts(self::PRIVATE, $subject, $id)) {
            return false;
        }

        return ($this->channels[self::PRIVATE][$subject]['authorize'])($actor, $id) === true;
    }

    /**
     * The member data to publish for the actor on `presence-{subject}[={id}]`, or
     * null if they may not join. `$id` is null for a forum-wide channel.
     *
     * Only a non-empty array admits the actor. `[]`, `true`, `false` and null all
     * refuse: a presence channel exists to publish who is in it, and an empty or
     * missing payload is far more likely a callback's "no" than a deliberate "yes,
     * with nothing to say".
     *
     * Presence channels carry a member list keyed by user id, so unlike private
     * channels they are inherently members-only; the caller rejects guests before
     * reaching here.
     *
     * @return array<string, mixed>|null
     */
    public function authorizePresence(string $subject, User $actor, ?int $id): ?array
    {
        if (! $this->accepts(self::PRESENCE, $subject, $id)) {
            return null;
        }

        $data = ($this->channels[self::PRESENCE][$subject]['authorize'])($actor, $id);

        return is_array($data) && $data !== [] ? $data : null;
    }

    /**
     * Two extensions claiming one subject must not silently give one channel the
     * other's permissions. Throwing here would not help either: this runs when the
     * registry is first resolved — on the first websocket auth request, not while
     * the forum boots — so it would turn every channel, built-in ones included,
     * into a 500 with no obvious cause.
     *
     * So a clash fails closed for that subject only: neither registration is used,
     * the rest of realtime keeps working, and the clash is logged. Prefix subjects
     * to avoid it — `acme-readers` rather than `readers`.
     *
     * (A subject registered twice through one extender is caught earlier, by the
     * extender itself, while `extend.php` is evaluated.)
     */
    private function add(string $type, string $subject, callable $authorize, bool $scoped): void
    {
        if (isset($this->channels[$type][$subject])) {
            $this->conflicted[$type][$subject] = true;

            $this->logger?->error(
                "[realtime] The $type channel subject \"$subject\" is registered more than once. "
                .'It will refuse every subscription until only one extension registers it.'
            );

            return;
        }

        $this->channels[$type][$subject] = ['scoped' => $scoped, 'authorize' => $authorize];
    }
}
