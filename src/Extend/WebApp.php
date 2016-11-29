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

use Flarum\Event\ConfigureWebApp;
use Flarum\Extension\Extension;
use Flarum\Forum\Controller\WebAppController as ForumWebAppController;
use Flarum\Admin\Controller\WebAppController as AdminWebAppController;

class WebApp
{
    protected $controller;

    protected $assets = [];

    protected $modules = [];

    public function __construct($controller = null)
    {
        $this->controller = $controller;
    }

    public function __invoke(Extension $extension)
    {
        return new Listener(function (ConfigureWebApp $event) use ($extension) {
            if (! $this->controller || $event->controller instanceof $this->controller) {
                $event->addAssets($this->prefixFiles($this->assets, $extension));
                $event->view->loadModule($this->prefixModules($this->modules, $extension));
            }
        });
    }

    public function assets($file)
    {
        $this->assets = array_merge($this->assets, (array) $file);

        return $this;
    }

    public function module($name = 'main')
    {
        $this->modules = array_merge($this->modules, (array) $name);

        return $this;
    }

    protected function prefixFiles(array $files, Extension $extension)
    {
        return array_map(function ($file) use ($extension) {
            return ($file[0] === '/' ? '' : $extension->getPath()).$file;
        }, $files);
    }

    protected function prefixModules(array $modules, Extension $extension)
    {
        return array_map(function ($name) use ($extension) {
            return $extension->getId().'/'.$name;
        }, $modules);
    }

    public static function forum()
    {
        return new static(ForumWebAppController::class);
    }

    public static function admin()
    {
        return new static(AdminWebAppController::class);
    }

    public static function defaultAssets()
    {
        return [
            static::forum()
                ->assets([
                    'js/forum/dist/extension.js',
                    'less/forum/extension.less'
                ])
                ->module(),

            static::admin()
                ->assets([
                    'js/admin/dist/extension.js',
                    'less/admin/extension.less'
                ])
                ->module()
        ];
    }
}
