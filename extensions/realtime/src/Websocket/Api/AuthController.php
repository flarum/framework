<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Pusher\Pusher;

/**
 * Signs a client's subscription to a websocket channel, once the
 * {@link ChannelRegistry} says the actor may join it.
 *
 * This only routes: it parses the channel name into a subject and an optional id,
 * and hands those to the registry. Which channels exist, and what each one
 * requires, lives there — including realtime's own, which are registered from
 * `extend.php` like any extension's.
 */
class AuthController implements RequestHandlerInterface
{
    /**
     * Both allow a hyphenated subject, so a multi-word channel needs no special
     * case (`private-index-typing-tag={id}` used to need one).
     *
     * Private channels address a single subject instance and always carry an id.
     * Presence channels may omit it: `presence-online` is forum-wide, while a
     * per-object roster is `presence-{subject}={id}`.
     */
    private const PRIVATE_CHANNEL = '~^private-(?<subject>[a-zA-Z][a-zA-Z0-9-]*)=(?<id>[0-9]+)$~';
    private const PRESENCE_CHANNEL = '~^presence-(?<subject>[a-zA-Z][a-zA-Z0-9-]*)(?:=(?<id>[0-9]+))?$~';

    public function __construct(
        protected Pusher $pusher,
        protected ChannelRegistry $registry,
        protected PresenceChannelAuthorizer $presenceAuthorizer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $attributes = $request->getParsedBody();

        $actor = RequestUtil::getActor($request);
        $channel = Arr::get($attributes, 'channel_name');
        $socketId = Arr::get($attributes, 'socket_id');

        if (! is_string($channel)) {
            return new EmptyResponse(403);
        }

        if (preg_match(self::PRIVATE_CHANNEL, $channel, $m)) {
            if ($this->registry->authorizePrivate($m['subject'], $actor, (int) $m['id'])) {
                return new JsonResponse(json_decode(
                    $this->pusher->authorizeChannel($channel, $socketId),
                    true
                ));
            }

            return new EmptyResponse(403);
        }

        if (preg_match(self::PRESENCE_CHANNEL, $channel, $m)) {
            // A presence channel publishes a member list keyed by user id, so there
            // is nothing to put in it for a guest.
            if ($actor->isGuest() || ! $this->presenceAuthorizer->authorize($m['subject'], $actor)) {
                return new EmptyResponse(403);
            }

            $id = ($m['id'] ?? '') === '' ? null : (int) $m['id'];
            $memberData = $this->registry->authorizePresence($m['subject'], $actor, $id);

            if ($memberData !== null) {
                return new JsonResponse(json_decode(
                    $this->pusher->authorizePresenceChannel(
                        $channel,
                        $socketId,
                        (string) $actor->id,
                        $memberData
                    ),
                    true
                ));
            }
        }

        return new EmptyResponse(403);
    }
}
