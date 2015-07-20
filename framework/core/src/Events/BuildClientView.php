<?php namespace Flarum\Events;

use Flarum\Support\ClientAction;
use Flarum\Support\ClientView;
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
    public function __construct($action, $view, &$keys)
    {
        $this->action = $action;
        $this->view = $view;
        $this->keys = &$keys;
    }

    public function forumAssets($files)
    {
        if ($this->action instanceof ForumClientAction) {
            $this->view->getAssets()->addFiles((array) $files);
        }
    }

    public function forumBootstrapper($bootstrapper)
    {
        if ($this->action instanceof ForumClientAction) {
            $this->view->addBootstrapper($bootstrapper);
        }
    }

    public function forumTranslations(array $keys)
    {
        if ($this->action instanceof ForumClientAction) {
            foreach ($keys as $key) {
                $this->keys[] = $key;
            }
        }
    }}
