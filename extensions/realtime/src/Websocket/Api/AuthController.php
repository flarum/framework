<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Realtime\Push\Payload\Generator;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Pusher\Pusher;

class AuthController implements RequestHandlerInterface
{
    private User $actor;

    public function __construct(
        protected Pusher $pusher,
        protected Generator $generator,
        protected PresenceChannelAuthorizer $presenceAuthorizer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $attributes = $request->getParsedBody();

        $this->actor = RequestUtil::getActor($request);
        $channel = Arr::get($attributes, 'channel_name');

        if (preg_match('~^private-index-typing-tag=(?<id>[0-9]+)$~', $channel, $m)) {
            if ($this->indexTypingTag((int) $m['id'])) {
                $socketId = Arr::get($attributes, 'socket_id');
                $body = $this->pusher->authorizeChannel($channel, $socketId);

                return new JsonResponse(json_decode($body, true));
            }

            return new EmptyResponse(403);
        }

        if (preg_match('~^private-(?<subject>[a-zA-Z]+)=(?<id>[0-9]+)$~', $channel, $m)) {
            if (method_exists($this, $m['subject']) && call_user_func([$this, $m['subject']], $m['id'])) {
                $socketId = Arr::get($attributes, 'socket_id');

                // Compute the auth body
                $body = $this->pusher->authorizeChannel($channel, $socketId);

                return new JsonResponse(json_decode($body, true));
            }
        }

        if (preg_match('~^presence-(?<subject>[a-z-]+)$~', $channel, $m)) {
            if (! $this->actor->isGuest() && method_exists($this, $m['subject'])
                && $this->presenceAuthorizer->authorize($m['subject'], $this->actor)
            ) {
                $payload = call_user_func([$this, $m['subject']], $this->actor);

                // Only if the method returns anything, will we allow authentication.
                if ($payload) {
                    $socketId = Arr::get($attributes, 'socket_id');
                    $body = $this->pusher->authorizePresenceChannel(
                        $channel,
                        $socketId,
                        (string) $this->actor->id,
                        $payload
                    );

                    return new JsonResponse(json_decode($body, true));
                }
            }
        }

        return new EmptyResponse(403);
    }

    protected function user(int $id): bool
    {
        return ! $this->actor->isGuest() && $this->actor->id === $id;
    }

    protected function typing(int $id): bool
    {
        return Discussion::whereVisibleTo($this->actor)->where('id', $id)->exists();
    }

    protected function privateMessageTyping(int $id): bool
    {
        return \Flarum\Messages\Dialog::whereVisibleTo($this->actor)->where('id', $id)->exists();
    }

    /**
     * Authorize a restricted-tag index-typing channel: the actor may listen iff
     * they can see the tag. Reaching this without flarum-tags active is rejected.
     */
    protected function indexTypingTag(int $id): bool
    {
        if (! class_exists(\Flarum\Tags\Tag::class)) {
            return false;
        }

        return \Flarum\Tags\Tag::whereVisibleTo($this->actor)->where('id', $id)->exists();
    }

    protected function online(User $actor): array
    {
        // @todo It returns []
        $generate = $this->generator;

        return [
            'displayName' => $actor->display_name
        ];
    }
}
