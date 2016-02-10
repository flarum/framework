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

use Flarum\Foundation\Application;
use Flarum\Http\Controller\AbstractClientController as BaseClientController;
use Flarum\Extension\ExtensionManager;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Core\Permission;
use Flarum\Api\Client;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Event\PrepareUnserializedSettings;

class ClientController extends BaseClientController
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'admin';

    /**
     * {@inheritdoc}
     */
    protected $translations = '/[^\.]\.(admin|lib)\.*+/';

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Application $app,
        Client $apiClient,
        LocaleManager $locales,
        SettingsRepositoryInterface $settings,
        Dispatcher $events,
        Repository $cache,
        ExtensionManager $extensions
    ) {
        BaseClientController::__construct($app, $apiClient, $locales, $settings, $events, $cache);

        $this->layout = __DIR__.'/../../../views/admin.blade.php';
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        $view = BaseClientController::render($request);

        $settings = $this->settings->all();

        $this->events->fire(
            new PrepareUnserializedSettings($settings)
        );

        $view->setVariable('settings', $settings);
        $view->setVariable('permissions', Permission::map());
        $view->setVariable('extensions', $this->extensions->getInfo());

        return $view;
    }
}
