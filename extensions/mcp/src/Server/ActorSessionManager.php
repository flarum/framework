<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Mcp\Server\Session\SessionInterface;
use Mcp\Server\Session\SessionManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Binds each handshake-era session to the actor that opened it: the actor's ID
 * (0 for a guest) is stored on `initialize`, and a session ID presented by
 * anyone else does not exist for them. The SDK then answers as it does for an
 * unknown session (HTTP 404), and a DELETE by someone else destroys nothing.
 *
 * @internal
 */
class ActorSessionManager implements SessionManagerInterface
{
    public const string ACTOR_KEY = 'flarum_actor_id';

    public function __construct(
        protected SessionManagerInterface $sessions,
        protected int $actorId,
    ) {
    }

    public function create(): SessionInterface
    {
        $session = $this->sessions->create();
        $session->set(static::ACTOR_KEY, $this->actorId);

        return $session;
    }

    public function createWithId(Uuid $id): SessionInterface
    {
        return $this->sessions->createWithId($id);
    }

    public function exists(Uuid $id): bool
    {
        return $this->sessions->exists($id)
            && $this->sessions->createWithId($id)->get(static::ACTOR_KEY) === $this->actorId;
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
