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

use DateTime;
use Flarum\Admin\WebApp;
use Flarum\Core\Discussion;
use Flarum\Core\Permission;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\PrepareUnserializedSettings;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\Controller\AbstractWebAppController;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface;

class WebAppController extends AbstractWebAppController
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
     * @param WebApp $webApp
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param ExtensionManager $extensions
     * @param ConnectionInterface $db
     */
    public function __construct(WebApp $webApp, Dispatcher $events, SettingsRepositoryInterface $settings, ExtensionManager $extensions, ConnectionInterface $db)
    {
        $this->webApp = $webApp;
        $this->events = $events;
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

        $this->events->fire(
            new PrepareUnserializedSettings($settings)
        );

        $view->setVariable('settings', $settings);
        $view->setVariable('permissions', Permission::map());
        $view->setVariable('extensions', $this->extensions->getExtensions()->toArray());

        $view->setVariable('phpVersion', PHP_VERSION);
        $view->setVariable('mysqlVersion', $this->db->selectOne('select version() as version')->version);

        $view->setVariable('statistics', $this->getStatistics());

        return $view;
    }

    private function getStatistics()
    {
        return [
            'total' => $this->getEntityCounts(),
            'month' => $this->getEntityCounts(new DateTime('-28 days')),
            'week' => $this->getEntityCounts(new DateTime('-7 days')),
            'today' => $this->getEntityCounts(new DateTime('-1 day'))
        ];
    }

    private function getEntityCounts($since = null)
    {
        $queries = [
            'users' => User::query(),
            'discussions' => Discussion::query(),
            'posts' => Post::where('type', 'comment')
        ];

        if ($since) {
            $queries['users']->where('join_time', '>', $since);
            $queries['discussions']->where('start_time', '>', $since);
            $queries['posts']->where('time', '>', $since);
        }

        return array_map(function ($query) {
            return $query->count();
        }, $queries);
    }
}
