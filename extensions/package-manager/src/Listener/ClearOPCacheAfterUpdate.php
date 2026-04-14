<?php

declare(strict_types=1);

namespace Flarum\ExtensionManager\Listener;

use Flarum\Foundation\Paths;
use Flarum\Http\Middleware\ClearOPCache;

readonly class ClearOPCacheAfterUpdate
{
    public function __construct(private readonly Paths $paths)
    {
    }

    public function handle(): void
    {
        $path = $this->paths->storage . ClearOPCache::PATH;

        if (file_exists($path) || ! function_exists('opcache_reset')) {
            return;
        }

        @file_put_contents($path, (string) time());
    }
}
