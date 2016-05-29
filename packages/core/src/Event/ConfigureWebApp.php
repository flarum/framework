<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Admin\Controller\WebAppController as AdminWebAppController;
use Flarum\Forum\Controller\WebAppController as ForumWebAppController;
use Flarum\Http\Controller\AbstractWebAppController;
use Flarum\Http\WebApp\WebAppView;

class ConfigureWebApp
{
    /**
     * @var AbstractWebAppController
     */
    public $controller;

    /**
     * @var WebAppView
     */
    public $view;

    /**
     * @param AbstractWebAppController $controller
     * @param WebAppView $view
     */
    public function __construct(AbstractWebAppController $controller, WebAppView $view)
    {
        $this->controller = $controller;
        $this->view = $view;
    }

    public function isForum()
    {
        return $this->controller instanceof ForumWebAppController;
    }

    public function isAdmin()
    {
        return $this->controller instanceof AdminWebAppController;
    }

    public function addAssets($files)
    {
        foreach ((array) $files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            switch ($ext) {
                case 'js':
                    $this->view->getJs()->addFile($file);
                    break;

                case 'css':
                case 'less':
                    $this->view->getCss()->addFile($file);
                    break;
            }
        }
    }

    public function addBootstrapper($bootstrapper)
    {
        $this->view->loadModule($bootstrapper);
    }
}
