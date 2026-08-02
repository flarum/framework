<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket;

use Flarum\User\User;

/**
 * Resolves the display name and online-disclosure preference of a typing user.
 *
 * The typing payload used to carry both, asserted by the sender's own client. That
 * was tolerable while the name was only ever echoed back to the same audience that
 * could already see it — but once a name is disclosed to holders of
 * `user.viewLastSeenAt` (and withheld from everyone else) the server is making an
 * authority claim about who is typing, and a client-asserted string can't back it.
 * So the relay ignores what the payload claims and looks both values up here,
 * keyed on the identity the connection authenticated with (see
 * {@link \Flarum\Realtime\Websocket\Channel\Manager::userIdForConnection()}).
 *
 * This runs in-process inside the long-lived websocket server, on every typing
 * ping, so lookups are cached for a few seconds. The TTL is deliberately short:
 * it bounds how long a user who has just hidden their online status keeps being
 * announced by name.
 */
class TypingIdentity
{
    protected const TTL_MS = 5000;

    /**
     * Above this many cached users, prune expired entries before adding more, so a
     * busy forum can't grow the map without bound over the server's lifetime.
     */
    protected const PRUNE_THRESHOLD = 500;

    /**
     * userId => [displayName, discloseOnline, cachedAt].
     *
     * @var array<int, array{0: string, 1: bool, 2: float}>
     */
    protected array $cache = [];

    /**
     * The authoritative identity of a typing user, or null if there is no such user.
     *
     * @return array{displayName: string, discloseOnline: bool}|null
     */
    public function for(int $userId): ?array
    {
        $now = $this->now();

        if (isset($this->cache[$userId]) && ($now - $this->cache[$userId][2]) < self::TTL_MS) {
            [$displayName, $discloseOnline] = $this->cache[$userId];

            return compact('displayName', 'discloseOnline');
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return null;
        }

        if (count($this->cache) >= self::PRUNE_THRESHOLD) {
            $this->prune($now);
        }

        $displayName = (string) $user->display_name;
        $discloseOnline = (bool) $user->getPreference('discloseOnline');

        $this->cache[$userId] = [$displayName, $discloseOnline, $now];

        return compact('displayName', 'discloseOnline');
    }

    protected function prune(float $now): void
    {
        foreach ($this->cache as $userId => [, , $cachedAt]) {
            if (($now - $cachedAt) >= self::TTL_MS) {
                unset($this->cache[$userId]);
            }
        }
    }

    protected function now(): float
    {
        return microtime(true) * 1000;
    }
}
