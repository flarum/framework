<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Connection;

use Flarum\Realtime\Websocket\Channel\Channel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FetchChannels extends Controller
{
    public function __invoke(ServerRequestInterface $request): mixed
    {
        $attributes = [];
        $filterByPrefix = Arr::get($request->getQueryParams(), 'filter_by_prefix');

        if ($info = Arr::get($request->getQueryParams(), 'info')) {
            $attributes = explode(',', trim($info));

            if (in_array('user_count', $attributes) && ! Str::startsWith($filterByPrefix, 'presence-')) {
                throw new BadRequestException('Request must be limited to presence channels in order to fetch user_count');
            }
        }

        return $this->manager
            ->getChannels()
            ->then(function ($channels) use ($filterByPrefix, $attributes) {
                /** @phpstan-ignore-next-line */
                $channels = collect($channels)
                    ->mapWithKeys(function ($channel) {
                        $key = $channel instanceof Channel
                            ? $channel->getName()
                            : $channel;

                        return [
                            $key => new stdClass
                        ];
                    });

                if ($filterByPrefix) {
                    $channels = $channels->filter(function ($channel, $name) use ($filterByPrefix) {
                        return Str::startsWith($name, $filterByPrefix);
                    });
                }

                if (! in_array('user_count', $attributes)) {
                    return [
                        'channels' => $channels->all() ?: new stdClass
                    ];
                }

                return $this->manager
                    ->getChannelsMembersCount($channels->keys()->toArray())
                    ->then(function ($counts) use ($channels, $attributes) {
                        $channels = $channels->map(function ($channel, $name) use ($attributes, $counts) {
                            if (in_array('user_count', $attributes)) {
                                $channel->user_count = $counts[$name];
                            }

                            return $channel;
                        });

                        return [
                            'channels' => $channels->all() ?: new stdClass
                        ];
                    });
            });
    }
}
