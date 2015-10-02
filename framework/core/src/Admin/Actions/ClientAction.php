<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Actions;

use Flarum\Support\ClientAction as BaseClientAction;
use Flarum\Support\ClientView;
use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Core\Groups\Permission;
use Flarum\Api\Client;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Locale\LocaleManager;
use Flarum\Events\UnserializeConfig;
use Flarum\Events\BuildAdminClientView;

class ClientAction extends BaseClientAction
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'admin';

    /**
     * {@inheritdoc}
     */
    protected $translationKeys = ['core.admin'];

    /**
     * {@inheritdoc}
     */
    public function __construct(Client $apiClient, LocaleManager $locales, SettingsRepository $settings)
    {
        parent::__construct($apiClient, $locales, $settings);

        $this->layout = __DIR__.'/../../../views/admin.blade.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function fireEvent(ClientView $view, array &$keys)
    {
        event(new BuildAdminClientView($this, $view, $keys));
    }

    /**
     * {@inheritdoc}
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $config = $this->settings->all();

        event(new UnserializeConfig($config));

        $view->setVariable('config', $config);
        $view->setVariable('permissions', Permission::map());
        $view->setVariable('extensions', app('flarum.extensions')->getInfo());

        return $view;
    }
}
