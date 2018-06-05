<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Controller;

use Flarum\Admin\AdminFrontend;
use Flarum\Extension\ExtensionManager;
use Flarum\Frontend\AbstractFrontendController;
use Flarum\Group\Permission;
use Flarum\Settings\Event\Deserializing;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface;

class FrontendController extends AbstractFrontendController
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @param AdminFrontend $frontend
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param ExtensionManager $extensions
     * @param ConnectionInterface $db
     */
    public function __construct(AdminFrontend $frontend, Dispatcher $events, SettingsRepositoryInterface $settings, ExtensionManager $extensions, ConnectionInterface $db)
    {
        parent::__construct($frontend, $events);

        $this->settings = $settings;
        $this->extensions = $extensions;
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    protected function getView(ServerRequestInterface $request)
    {
        $view = parent::getView($request);

        $settings = $this->settings->all();

        $this->events->dispatch(
            new Deserializing($settings)
        );

        $view->setVariable('settings', $settings);
        $view->setVariable('permissions', Permission::map());
        $view->setVariable('extensions', $this->extensions->getExtensions()->toArray());

        $view->setVariable('phpVersion', PHP_VERSION);
        $view->setVariable('mysqlVersion', $this->db->selectOne('select version() as version')->version);

        return $view;
    }
}
