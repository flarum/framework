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
use Flarum\Http\UrlGenerator;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Container\Container;

class FormatterServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.formatter', function (Container $container) {
            $polyfillUrl = $container->make(UrlGenerator::class)->to('forum')->path('assets/xslt-polyfill/xslt-polyfill.min.js');
            if (($version = XsltPolyfill::version()) !== null) {
                $polyfillUrl .= '?v='.$version;
            }

            return new Formatter(
                new Repository($container->make('cache.filestore')),
                $container[Paths::class]->storage.'/formatter',
                $polyfillUrl
            );
        });

        $this->container->alias('flarum.formatter', Formatter::class);
    }
}
