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

readonly class Config implements ArrayAccess
{
    public function __construct(
        private array $data
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

    public function queueDriver(): ?string
    {
        return $this->data['queue']['driver'] ?? null;
    }

    /**
     * How long a token of the given type stays valid, in seconds.
     *
     * Keyed by the type each token class declares, so a type added by an
     * extension is configured the same way the ones in core are.
     *
     * Zero is meaningful — it means the token never expires — so it is only
     * values that cannot be honoured at all (negative, or not a number) that
     * are discarded here in favour of whatever comes next.
     */
    public function accessTokenLifetime(string $type): ?int
    {
        $lifetime = $this->data['session']['tokens'][$type] ?? null;

        if (! is_numeric($lifetime) || $lifetime < 0) {
            return null;
        }

        return (int) $lifetime;
    }

    /**
     * How long a session may sit idle before it is discarded, in minutes.
     */
    public function sessionLifetime(): ?int
    {
        $lifetime = $this->data['session']['lifetime'] ?? null;

        if (! is_numeric($lifetime) || $lifetime <= 0) {
            return null;
        }

        return (int) $lifetime;
    }

    /**
     * Whether session cookies should be discarded when the browser closes,
     * rather than lasting for the lifetime of the token behind them.
     */
    public function sessionCookieExpiresOnClose(): ?bool
    {
        $value = $this->data['session']['cookie_expires_on_close'] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * Whether session lifetimes are pinned in `config.php`, and so cannot be
     * changed from the admin panel.
     */
    public function sessionConfigOverride(): bool
    {
        return isset($this->data['session']);
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

    public function fontawesomeSource(): ?string
    {
        return $this->data['fontawesome']['source'] ?? null;
    }

    public function fontawesomeCdnUrl(): ?string
    {
        return $this->data['fontawesome']['cdn_url'] ?? null;
    }

    public function fontawesomeKitUrl(): ?string
    {
        return $this->data['fontawesome']['kit_url'] ?? null;
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
        return Arr::get($this->data, $offset, Arr::get($this->defaults(), $offset));
    }

    public function offsetExists($offset): bool
    {
        return Arr::has($this->data, $offset) || Arr::has($this->defaults(), $offset);
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

    protected function defaults(): array
    {
        // Mostly needed for Laravel internals.
        return [
            'app' => [
                'timezone' => 'UTC',
            ],
        ];
    }
}
