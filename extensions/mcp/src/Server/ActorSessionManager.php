<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\User\User;
use Mcp\Server\Session\SessionInterface;
use Mcp\Server\Session\SessionManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Binds each handshake-era session to the client that opened it: an owner key
 * (see {@see self::ownerOf()}) is stored on `initialize`, and a session ID
 * presented by anyone else does not exist for them. The SDK then answers as it
 * does for an unknown session (HTTP 404), and a DELETE by someone else
 * destroys nothing.
 *
 * @internal
 */
class ActorSessionManager implements SessionManagerInterface
{
    public const string ACTOR_KEY = 'flarum_session_owner';

    public function __construct(
        protected SessionManagerInterface $sessions,
        protected string $owner,
    ) {
    }

    /**
     * A signed-in actor owns a session by ID. Guests all share ID 0, so a
     * guest's session is also bound to a hash of the client's IP address as
     * the forum sees it (the request's `ipAddress`, from `REMOTE_ADDR`): a
     * guest elsewhere cannot use it, though a guest whose address changes
     * (a mobile network, a rotating proxy) must start a new session, and
     * guests behind one address (including every guest, behind a reverse
     * proxy that does not pass the client's address on) are told apart only
     * by the session ID.
     */
    public static function ownerOf(User $actor, ?string $ipAddress): string
    {
        return $actor->isGuest()
            ? 'guest:'.hash('sha256', (string) $ipAddress)
            : 'user:'.$actor->id;
    }

    public function create(): SessionInterface
    {
        $session = $this->sessions->create();
        $session->set(static::ACTOR_KEY, $this->owner);

        return $session;
    }

    public function createWithId(Uuid $id): SessionInterface
    {
        return $this->sessions->createWithId($id);
    }

    public function exists(Uuid $id): bool
    {
        return $this->sessions->exists($id)
            && $this->sessions->createWithId($id)->get(static::ACTOR_KEY) === $this->owner;
    }

    public function destroy(Uuid $id): bool
    {
        return $this->exists($id) && $this->sessions->destroy($id);
    }

    public function gc(): void
    {
        $this->sessions->gc();
    }
}
