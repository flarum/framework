<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class FormatterServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.formatter', function (Container $container) {
            return new Formatter(
                new Repository($container->make('cache.filestore')),
                $container[Paths::class]->storage.'/formatter'
            );
        });

        $this->container->alias('flarum.formatter', Formatter::class);
    }

    public function boot(Container $container): void
    {
        // Register a *resolver* rather than resolving the URL now. The
        // resolver fires lazily on the first getJs() call, so:
        //   1. We don't pre-resolve the flarum-assets disk during boot,
        //      which would memoise it before tests can swap the adapter.
        //   2. We don't pull UrlGenerator into early route resolution.
        //   3. Disks without a public URL (in-memory test disks, etc.)
        //      cause the resolver to return null and the polyfill loader
        //      to be skipped — the formatter falls back to plain s9e.
        $container->make(Formatter::class)->setXsltPolyfillUrlResolver(function () use ($container): ?string {
            try {
                $url = $container->make(FilesystemFactory::class)->disk('flarum-assets')->url('xslt-polyfill/xslt-polyfill.min.js');
            } catch (\RuntimeException) {
                return null;
            }

            if (($version = XsltPolyfill::version()) !== null) {
                $url .= '?v='.$version;
            }

            return $url;
        });
    }
}
