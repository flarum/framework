<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Frontend\Event\Rendering;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Assets implements Extender
{
    protected $appName;

    protected $assets = [];
    protected $bootstrapper;

    public function __construct($appName)
    {
        $this->appName = $appName;
    }

    public function asset($path)
    {
        $this->assets[] = $path;

        return $this;
    }

    public function bootstrapper($name)
    {
        $this->bootstrapper = $name;

        return $this;
    }

    public function __invoke(Container $container)
    {
        $container->make(Dispatcher::class)->listen(
            Rendering::class,
            function (Rendering $event) {
                if (! $this->matches($event)) {
                    return;
                }

                $event->addAssets($this->assets);

                if ($this->bootstrapper) {
                    $event->addBootstrapper($this->bootstrapper);
                }
            }
        );
    }

    private function matches(Rendering $event)
    {
        switch ($this->appName) {
            case 'admin':
                return $event->isAdmin();
            case 'forum':
                return $event->isForum();
            default:
                return false;
        }
    }
}
