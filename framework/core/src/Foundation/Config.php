<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use ArrayAccess;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class Config implements ArrayAccess
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->requireKeys('url');
    }

    public function url(): UriInterface
    {
        return new Uri(rtrim($this->data['url'], '/'));
    }

    public function inDebugMode(): bool
    {
        return $this->data['debug'] ?? false;
    }

    public function inMaintenanceMode(): bool
    {
        return $this->data['offline'] ?? false;
    }

    private function requireKeys(...$keys)
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->data)) {
                throw new InvalidArgumentException(
                    "Configuration is invalid without a $key key"
                );
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return Arr::get($this->data, $offset);
    }

    public function offsetExists($offset): bool
    {
        return Arr::has($this->data, $offset);
    }

    public function offsetSet($offset, $value): void
    {
        throw new RuntimeException('The Config is immutable');
    }

    public function offsetUnset($offset): void
    {
        throw new RuntimeException('The Config is immutable');
    }
}
