<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Locale\LocaleManager;
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
        if (! in_array($frontend, $this->assets)) {
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
