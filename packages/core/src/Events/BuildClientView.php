<?php namespace Flarum\Events;

use Flarum\Support\ClientAction;
use Flarum\Support\ClientView;

class BuildClientView
{
    /**
     * @var ClientAction
     */
    protected $action;

    /**
     * @var ClientView
     */
    protected $view;

    /**
     * @var array
     */
    protected $keys;

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
}
