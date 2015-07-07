<?php namespace Flarum\Forum\Actions;

use Flarum\Support\ClientAction as BaseClientAction;

abstract class ClientAction extends BaseClientAction
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'forum';

    /**
     * {@inheritdoc}
     */
    protected $layout = 'flarum.forum::forum';
}
