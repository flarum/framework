<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class AssetManager
{
    /**
     * @var array<string, string>
     */
    protected array $assets = [];

    public function __construct(
        protected readonly Container $container,
        protected readonly LocaleManager $locales
    ) {
    }

    public function frontend(string $frontend): Assets
    {
        // Keyed by frontend name ('forum'), the value being the container
        // abstract ('flarum.assets.forum') — so the guard must look at the
        // keys. Checking the values instead rejected every registered
        // frontend, while an abstract passed the guard and then resolved an
        // undefined key.
        if (! array_key_exists($frontend, $this->assets)) {
            throw new InvalidArgumentException("Unknown frontend: $frontend");
        }

        return $this->container->make($this->assets[$frontend]);
    }

    /**
     * @return array<Assets>
     * @throws BindingResolutionException
     */
    public function all(): array
    {
        return array_map(fn (string $abstract) => $this->container->make($abstract), $this->assets);
    }

    public function register(string $frontend, string $abstract): void
    {
        $this->assets[$frontend] = $abstract;
    }

    /**
     * Flag every frontend as needing a rebuild, without touching the compiled
     * files or their manifest revisions.
     *
     * The rebuild is deferred to the next freshly-booted request. That matters
     * for the settings this is called for: they change which extensions boot,
     * so a rebuild in the saving request — whose container booted before the
     * save — would compile the old extension set and record it as current.
     *
     * @see \Flarum\Frontend\RecompileFrontendAssets::markDirty()
     */
    public function markDirty(): void
    {
        foreach ($this->all() as $assets) {
            (new RecompileFrontendAssets(
                $assets,
                $this->locales,
                $this->container->make('events'),
                $this->container->make(SettingsRepositoryInterface::class)
            ))->markDirty();
        }
    }

    /**
     * @deprecated 2.1 Use {@see markDirty()} instead, which defers the rebuild
     * to a freshly-booted request rather than deleting the compiled files.
     *
     * Deleting them leaves already-served pages pointing at files that no
     * longer exist, and clears the revisions that tell the next request what
     * to rebuild — so whichever request arrives first recompiles from its own
     * container, which for a safe-mode save is the admin's own browser running
     * a reduced extension set.
     */
    public function flushJs(): void
    {
        foreach ($this->all() as $assets) {
            $assets->makeJs()->flush();

            foreach ($this->locales->getLocales() as $locale => $name) {
                $assets->makeLocaleJs($locale)->flush();
            }
        }
    }
}
