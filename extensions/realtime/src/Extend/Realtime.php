<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Realtime\Websocket\Settings;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class Realtime implements ExtenderInterface
{
    protected array $configuration = [];

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->afterResolving(Settings::class, function (Settings $settings) {
            $settings->use($this->configuration);
        });
    }

    /**
     * Provide the full url to the running `php flarum realtime:serve` daemon.
     * In case you proxy it, use the outside URL.
     *
     * @example https://wss.flarum.site
     * @example https://flarum.site:9001
     *
     * @see `php flarum realtime:serve --help`
     *
     * @param string $url
     * @return $this
     */
    public function daemonUrl(string $url): self
    {
        $url = parse_url($url);

        if (! empty($url['path'])) {
            throw new InvalidArgumentException('Paths are not possible in websocket connections.');
        }

        $this->configuration['php-client-secure'] = $url['scheme'] === 'https';
        $this->configuration['php-client-host'] = $url['host'];
        $this->configuration['php-client-port'] = $url['port'];

        $this->configuration['js-client-secure'] = $url['scheme'] === 'https';
        $this->configuration['js-client-host'] = $url['host'];
        $this->configuration['js-client-port'] = $url['port'];

        return $this;
    }

    /**
     * Set maximum number of allowed websocket connections. In case your server resources
     * are limited, make sure to set a sensible limit.
     *
     * @param int $connections
     * @return $this
     */
    public function maxConnections(int $connections): self
    {
        $this->configuration['max-connections'] = $connections;

        return $this;
    }

    /**
     * These define the app key and secret. The key is used by any client (guest, member, etc)
     * to connect to the websocket. The secret is used in the background for authorization requests.
     *
     * @param string $key
     * @param string $secret
     * @return $this
     */
    public function app(string $key, string $secret): self
    {
        $this->configuration['app-key'] = $key;
        $this->configuration['app-secret'] = $secret;

        return $this;
    }

    /**
     * Override the complete settings (or a part of it) using this one setter.
     *
     * @param array $settings
     * @return $this
     */
    public function use(array $settings): self
    {
        $this->configuration = array_merge($this->configuration, $settings);

        return $this;
    }
}
