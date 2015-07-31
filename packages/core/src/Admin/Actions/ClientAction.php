<?php namespace Flarum\Admin\Actions;

use Flarum\Support\ClientAction as BaseClientAction;
use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Core\Groups\Permission;

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

    /**
     * {@inheritdoc}
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $view->setVariable('config', $this->settings->all());
        $view->setVariable('locales', app('flarum.localeManager')->getLocales());
        $view->setVariable('permissions', Permission::map());

        return $view;
    }
}
