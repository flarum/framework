<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Support\ClientAction;
use Flarum\Support\ClientView;
use Flarum\Admin\Actions\ClientAction as AdminClientAction;
use Flarum\Forum\Actions\ClientAction as ForumClientAction;

class BuildClientView
{
    /**
     * @var ClientAction
     */
    public $action;

    /**
     * @var ClientView
     */
    public $view;

    /**
     * @var array
     */
    public $keys;

    /**
     * @param ClientAction $action
     * @param ClientView $view
     * @param array $keys
     */
    public function __construct(ClientAction $action, ClientView $view, array &$keys)
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
