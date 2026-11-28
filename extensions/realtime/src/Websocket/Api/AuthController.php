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
 * This only routes: it parses the channel name into a type, a subject and an
 * optional id, and hands those to the registry. Which channels exist, whether each
 * takes an id, and what each one requires, lives there — including realtime's
 * own, which are registered from `extend.php` like any extension's.
 */
class AuthController implements RequestHandlerInterface
{
    /**
     * A hyphenated subject needs no special case (`private-index-typing-tag={id}`
     * and `private-typing-activity` used to). The id is optional in the pattern;
     * whether a given channel requires or forbids one is the registry's call.
     */
    private const CHANNEL = '~^(?<type>private|presence)-(?<subject>[a-zA-Z][a-zA-Z0-9-]*)(?:=(?<id>[0-9]+))?$~';

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

        if (! is_string($channel) || ! preg_match(self::CHANNEL, $channel, $m)) {
            return new EmptyResponse(403);
        }

        $subject = $m['subject'];
        $id = ($m['id'] ?? '') === '' ? null : (int) $m['id'];

        if ($m['type'] === ChannelRegistry::PRIVATE) {
            if ($this->registry->authorizePrivate($subject, $actor, $id)) {
                return new JsonResponse(json_decode(
                    $this->pusher->authorizeChannel($channel, $socketId),
                    true
                ));
            }

            return new EmptyResponse(403);
        }

        // A presence channel publishes a member list keyed by user id, so there is
        // nothing to put in it for a guest. The registry is consulted before the
        // guards so that they only ever run for channels that exist.
        if ($actor->isGuest()
            || ! $this->registry->accepts(ChannelRegistry::PRESENCE, $subject, $id)
            || ! $this->presenceAuthorizer->authorize($subject, $actor)) {
            return new EmptyResponse(403);
        }

        $memberData = $this->registry->authorizePresence($subject, $actor, $id);

        if ($memberData === null) {
            return new EmptyResponse(403);
        }

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
