<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket;

use Flarum\Foundation\Config;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property string $serverHost
 * @property int $serverPort
 * @property string $jsClientHost
 * @property int $jsClientPort
 * @property bool $jsClientSecure
 * @property string $phpClientHost
 * @property int $phpClientPort
 * @property bool $phpClientSecure
 * @property int $phpClientTimeout
 * @property int $maxConnections
 * @property string $appKey
 * @property string $appSecret
 */
class Settings implements Arrayable
{
    protected ?array $settings = null;
    protected array $defaults;

    public function __construct(private Config $config)
    {
        $this->defaults = $this->defaults();
    }

    private function defaults(): array
    {
        $host = trim(parse_url($this->config->url(), PHP_URL_HOST));
        $secure = parse_url($this->config->url(), PHP_URL_SCHEME) === 'https';
        $dbPassword = trim($this->config->offsetGet('database.password'));

        // Some sane defaults.
        $defaults = [
            'server-host' => '0.0.0.0',
            'server-port' => 6001,
            'js-client-host' => $host,
            'js-client-port' => 6001,
            'js-client-secure' => $secure,
            'php-client-host' => $host,
            'php-client-port' => 6001,
            'php-client-secure' => $secure,
            'php-client-timeout' => 3,
            'max-connections' => 1000,
            'app-key' => md5($host),
            'app-secret' => md5($dbPassword),
        ];

        // Override anything being set in the `config.php` under `websocket`.
        foreach ($defaults as $key => $default) {
            $defaults[$key] = $this->config->offsetGet("websocket.$key") ?? $default;
        }

        return $defaults;
    }

    public function use(array $settings): void
    {
        $this->settings = $settings;
    }

    protected function rules(): array
    {
        return [
            'server-host' => 'required|string',
            'server-port' => 'required|int',
            'js-client-host' => 'required|string',
            'js-client-port' => 'required|int',
            'js-client-secure' => 'bool|nullable',
            'php-client-host' => 'required|string',
            'php-client-port' => 'required|int',
            'php-client-secure' => 'bool|nullable',
            'php-client-timeout' => 'required|int|min:1',
            'max-connections' => 'required|int|min:1',
            'app-key' => 'required|string',
            'app-secret' => 'required|string'
        ];
    }

    public function __get(string $name): mixed
    {
        $name = Str::snake($name, '-');

        return Arr::get($this->toArray(), $name);
    }

    public function toArray()
    {
        $config = [];

        foreach ($this->rules() as $key => $_) {
            $config[$key] = $this->settings[$key]
                ?? Arr::get($this->defaults, $key);
        }

        $this->validate($config);

        return $config;
    }

    protected function validate(array $settings): void
    {
        /** @var Factory $validator */
        $validator = resolve(Factory::class);

        $validate = $validator->make($settings, $this->rules());

        try {
            $validate->validate();
        } catch (ValidationException $e) {
            throw new \InvalidArgumentException(join('\n', Arr::flatten($e->validator->errors()->getMessages())));
        }
    }
}
