<?php namespace Flarum\Admin\Actions;

use Flarum\Support\ClientAction as BaseClientAction;

class ClientAction extends BaseClientAction
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'admin';

    /**
     * {@inheritdoc}
     */
    protected $layout = __DIR__.'/../../../views/admin.blade.php';

    /**
     * {@inheritdoc}
     */
    protected $translationKeys = [

    ];
}
