<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Foundation\AbstractServiceProvider;

class ExtensionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->bind('flarum.extensions', 'Flarum\Extension\ExtensionManager');

        $config = $this->app->make('flarum.settings')->get('extensions_enabled');
        $extensions = json_decode($config, true);

        foreach ($extensions as $extension) {
            if (file_exists($file = public_path().'/extensions/'.$extension.'/bootstrap.php')) {
                $bootstrapper = require $file;

                $this->app->call($bootstrapper);
            }
        }
    }
}
