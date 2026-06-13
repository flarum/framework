<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Realtime\Websocket\Api\PresenceChannelAuthorizer;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\IndexTypingPresence;
use Flarum\Realtime\Websocket\Settings;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;

class WebsocketProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(RealtimeRegistry::class);
        $this->container->singleton(Manager::class);
        $this->container->singleton(IndexTypingPresence::class);
        $this->container->singleton(PresenceChannelAuthorizer::class);

        $this->container->singleton(Pusher::class, function (Container $container) {
            /** @var Settings $settings */
            $settings = $container->make(Settings::class);
            /** @var Config $config */
            $config = $container->make(Config::class);

            $pusher = new Pusher(
                $settings->appKey,
                $settings->appSecret,
                '1',
                [
                    'scheme' => $settings->phpClientSecure ? 'https' : 'http',
                    'host' => $settings->phpClientHost,
                    'port' => $settings->phpClientPort,
                    'debug' => $config->inDebugMode(),
                    'timeout' => $settings->phpClientTimeout
                ],
            );

            $pusher->setLogger($container->make(LoggerInterface::class));

            return $pusher;
        });
    }
}
