<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;

/**
 * Hands the translator a cache that knows which translations its catalogue was
 * built from. See {@see CatalogueCache}.
 *
 * The revision is resolved lazily, per call: the translator initialises a
 * catalogue the first time one is needed, by which point every extension has
 * registered its translation files — asking any earlier would describe a
 * smaller set than the catalogue is actually built from.
 */
class CatalogueCacheFactory implements ConfigCacheFactoryInterface
{
    /**
     * @param \Closure(): string $revision
     */
    public function __construct(
        private readonly \Closure $revision
    ) {
    }

    public function cache(string $file, callable $callback): ConfigCacheInterface
    {
        $cache = new CatalogueCache($file, ($this->revision)());

        if (! $cache->isFresh()) {
            $callback($cache);
        }

        return $cache;
    }
}
