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
        // Resolve the polyfill URL via the flarum-assets disk so it stays
        // correct on installs whose assets are served from a remote bucket
        // or CDN — same approach MailServiceProvider uses for the email logo.
        // Done in boot() rather than the formatter's register() closure to
        // avoid pulling UrlGenerator into formatter resolution, which caused
        // early route compilation that broke unrelated tests.
        $url = $container->make(FilesystemFactory::class)->disk('flarum-assets')->url('xslt-polyfill/xslt-polyfill.min.js');

        if (($version = XsltPolyfill::version()) !== null) {
            $url .= '?v='.$version;
        }

        $container->make(Formatter::class)->setXsltPolyfillUrl($url);
    }
}
