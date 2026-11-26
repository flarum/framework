<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Discover;

use Flarum\Extension\AbandonedExtensionsFetcher;

/**
 * Where it names a package, it wins over Packagist and composer.lock, as it does on core's extension pages.
 */
class AbandonedList
{
    protected const PACKAGE_NAME = '#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#';

    /** @param array<string,?string> $entries lowercase name => replacement */
    final public function __construct(protected array $entries = [])
    {
    }

    public static function fromCore(AbandonedExtensionsFetcher $fetcher, bool $fetch = true): static
    {
        return static::fromMap($fetcher->getFullMap($fetch));
    }

    /**
     * @param array<mixed> $map the list's own shape: name => {replacement?: string}
     */
    public static function fromMap(array $map): static
    {
        $entries = [];

        foreach ($map as $name => $entry) {
            if (! is_string($name) || $name === '' || ! is_array($entry)) {
                continue;
            }

            $name = strtolower($name);
            $replacement = strtolower(trim(is_string($entry['replacement'] ?? null) ? $entry['replacement'] : ''));

            if ($replacement === $name || ! preg_match(self::PACKAGE_NAME, $replacement)) {
                $replacement = null;
            }

            $entries[$name] = $replacement;
        }

        return new static($entries);
    }

    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->entries);
    }

    public function replacement(string $name): ?string
    {
        return $this->entries[strtolower($name)] ?? null;
    }

    /** Composer's shape for an abandoned flag: the replacement, or true. */
    public function flag(string $name): string|bool
    {
        return $this->has($name) ? ($this->replacement($name) ?? true) : false;
    }
}
