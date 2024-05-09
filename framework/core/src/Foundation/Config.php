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
    public function __construct(
        private readonly array $data
    ) {
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
        return $this->inHighMaintenanceMode() || $this->inLowMaintenanceMode() || $this->inSafeMode();
    }

    public function inHighMaintenanceMode(): bool
    {
        return $this->maintenanceMode() === MaintenanceMode::HIGH;
    }

    public function inLowMaintenanceMode(): bool
    {
        return $this->maintenanceMode() === MaintenanceMode::LOW;
    }

    public function inSafeMode(): bool
    {
        return $this->maintenanceMode() === MaintenanceMode::SAFE;
    }

    public function maintenanceMode(): string
    {
        return match ($mode = $this->data['offline'] ?? MaintenanceMode::NONE) {
            true => MaintenanceMode::HIGH,
            false => MaintenanceMode::NONE,
            default => $mode,
        };
    }

    public function safeModeExtensions(): ?array
    {
        return $this->data['safe_mode_extensions'] ?? null;
    }

    private function requireKeys(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $this->data)) {
                throw new InvalidArgumentException(
                    "Configuration is invalid without a $key key"
                );
            }
        }
    }

    public function offsetGet($offset): mixed
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

    public function environment(): string
    {
        return $this->data['env'] ?? 'production';
    }
}
