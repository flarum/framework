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

use Flarum\Extension\Extension;
use Flarum\Frontend\Event\Rendering;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Assets implements ExtenderInterface
{
    protected $appName;

    protected $assets = [];
    protected $js;

    public function __construct($appName)
    {
        $this->appName = $appName;
    }

    public function asset($path)
    {
        $this->assets[] = $path;

        return $this;
    }

    public function js($path)
    {
        $this->js = $path;

        return $this;
    }

    public function __invoke(Container $container, Extension $extension = null)
    {
        $container->make(Dispatcher::class)->listen(
            Rendering::class,
            function (Rendering $event) use ($extension) {
                if (! $this->matches($event)) {
                    return;
                }

                $event->addAssets($this->assets);

                if ($this->js) {
                    $event->view->getJs()->addString(function () use ($extension) {
                        $name = $extension->getId();

                        return 'var module={};'.file_get_contents($this->js).";\nflarum.extensions['$name']=module.exports";
                    });
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
