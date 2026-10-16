<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Discover;

/**
 * A small on-disk cache with an expiry.
 *
 * 🚨 A miss is never an error and a corrupt entry is never fatal. This holds
 * nothing that cannot be fetched again, so every failure path here returns
 * "not cached" and lets the caller do the work. A cache that can break the
 * feature it speeds up is worse than no cache.
 */
class Cache
{
    public function __construct(
        private string $dir,
        private int $ttl = 86400,
    ) {
    }

    /** @return array<string,mixed>|null */
    public function get(string $key): ?array
    {
        $path = $this->path($key);

        if (! is_file($path) || (time() - (int) filemtime($path)) > $this->ttl) {
            return null;
        }

        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    /** @param array<string,mixed> $value */
    public function put(string $key, array $value): void
    {
        if (! is_dir($this->dir) && ! @mkdir($this->dir, 0775, true) && ! is_dir($this->dir)) {
            return;
        }

        @file_put_contents($this->path($key), json_encode($value));
    }

    private function path(string $key): string
    {
        // Hashed, so a package name can never become a path.
        return $this->dir.'/'.sha1($key).'.json';
    }
}
