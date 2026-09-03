<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\User\User;
use InvalidArgumentException;

/**
 * The set of channels clients may subscribe to, and how each one is authorized.
 *
 * A channel name is `{private|presence}-{subject}[={id}]`; the subject selects an
 * entry here and the callback decides whether this actor may join. Everything
 * realtime itself offers is registered like anything else (see the extension's
 * `extend.php`), so there is one code path rather than a built-in set plus an
 * extension set.
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
    /**
     * @var array<string, callable(User, int): bool>
     */
    private array $private = [];

    /**
     * @var array<string, callable(User, ?int): (array|bool|null)>
     */
    private array $presence = [];

    /**
     * @param callable(User $actor, int $id): bool $authorize
     */
    public function addPrivate(string $subject, callable $authorize): void
    {
        $this->assertUnclaimed('private', $subject, isset($this->private[$subject]));

        $this->private[$subject] = $authorize;
    }

    /**
     * @param callable(User $actor, ?int $id): (array|bool|null) $authorize
     */
    public function addPresence(string $subject, callable $authorize): void
    {
        $this->assertUnclaimed('presence', $subject, isset($this->presence[$subject]));

        $this->presence[$subject] = $authorize;
    }

    /**
     * Whether the actor may subscribe to `private-{subject}={id}`.
     *
     * An unregistered subject is indistinguishable from a refused one — both are
     * simply "no", so a caller cannot probe for which channels exist.
     *
     * Note that guests reach these callbacks: private channels have never required
     * a session (the discussion typing channel is authorized for anyone who can
     * see the discussion, guests included). A channel that should be members-only
     * has to say so itself.
     */
    public function authorizePrivate(string $subject, User $actor, int $id): bool
    {
        $authorize = $this->private[$subject] ?? null;

        return $authorize !== null && $authorize($actor, $id) === true;
    }

    /**
     * The member data to publish for the actor on `presence-{subject}[={id}]`, or
     * null if they may not join. `$id` is null for a forum-wide channel.
     *
     * Presence channels carry a member list keyed by user id, so unlike private
     * channels they are inherently members-only; the caller rejects guests before
     * reaching here.
     *
     * @return array<string, mixed>|null
     */
    public function authorizePresence(string $subject, User $actor, ?int $id): ?array
    {
        $authorize = $this->presence[$subject] ?? null;

        if ($authorize === null) {
            return null;
        }

        $data = $authorize($actor, $id);

        return is_array($data) ? $data : null;
    }

    /**
     * Two extensions claiming one subject would silently give the loser's channel
     * the winner's permissions, so this fails loudly instead. Prefix subjects to
     * avoid it — `acme-readers` rather than `readers`.
     */
    private function assertUnclaimed(string $type, string $subject, bool $taken): void
    {
        if ($taken) {
            throw new InvalidArgumentException(
                "The $type channel subject \"$subject\" is already registered."
            );
        }
    }
}
