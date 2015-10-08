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
     * @var \Flarum\Http\Controller\AbstractClientController
     */
    public $action;

    /**
     * @var \Flarum\Http\\Flarum\Http\Controller\ClientView
     */
    public $view;

    /**
     * @var array
     */
    public $keys;

    /**
     * @param \Flarum\Http\\Flarum\Http\Controller\AbstractClientController $action
     * @param \Flarum\Http\\Flarum\Http\Controller\ClientView $view
     * @param array $keys
     */
    public function __construct(AbstractClientController $action, ClientView $view, array &$keys)
    {
        $this->action = $action;
        $this->view = $view;
        $this->keys = &$keys;
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

    public function addTranslations(array $keys)
    {
        foreach ($keys as $key) {
            $this->keys[] = $key;
        }
    }
}
