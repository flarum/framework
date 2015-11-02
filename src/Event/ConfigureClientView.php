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

use Flarum\Http\Controller\AbstractClientController;
use Flarum\Http\Controller\ClientView;
use Flarum\Admin\Controller\ClientController as AdminClientAction;
use Flarum\Forum\Controller\ClientController as ForumClientAction;

class ConfigureClientView
{
    /**
     * @var AbstractClientController
     */
    public $action;

    /**
     * @var ClientView
     */
    public $view;

    /**
     * @param AbstractClientController $action
     * @param ClientView $view
     */
    public function __construct(AbstractClientController $action, ClientView $view)
    {
        $this->action = $action;
        $this->view = $view;
    }

    public function isForum()
    {
        return $this->action instanceof ForumClientAction;
    }

    public function isAdmin()
    {
        return $this->action instanceof AdminClientAction;
    }

    public function addAssets($files)
    {
        $this->view->getAssets()->addFiles((array) $files);
    }

    public function addBootstrapper($bootstrapper)
    {
        $this->view->addBootstrapper($bootstrapper);
    }
}
