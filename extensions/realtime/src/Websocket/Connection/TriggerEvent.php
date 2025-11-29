<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Connection;

use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class TriggerEvent extends Controller
{
    public function __invoke(ServerRequestInterface $request): mixed
    {
        $params = $request->getParsedBody();

        $channels = Arr::get($params, 'channels')
            ?? Arr::get($params, 'channel', []);

        if (is_string($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channelName) {
            $channel = $this->manager->find($channelName);

            $payload = [
                'event' => $params['name'],
                'channel' => $channelName,
                'data' => $params['data'],
            ];

            if ($channel) {
                $channel->broadcastToEveryoneExcept(
                    (object) $payload,
                    $request->getAttribute('socket_id')
                );
            }
        }

        $params['channels'] = (object) $channels;

        return new JsonResponse($params);
    }
}
