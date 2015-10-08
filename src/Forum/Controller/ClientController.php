<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepository;
use Flarum\Locale\LocaleManager;
use Flarum\Http\Controller\AbstractClientController;
use Illuminate\Contracts\Events\Dispatcher;

class ClientController extends AbstractClientController
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'forum';

    /**
     * {@inheritdoc}
     */
    protected $translationKeys = ['core.forum', 'core.lib'];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Application $app,
        Client $api,
        LocaleManager $locales,
        SettingsRepository $settings,
        Dispatcher $events
    ) {
        parent::__construct($app, $api, $locales, $settings, $events);

        $this->layout = __DIR__.'/../../../views/forum.blade.php';
    }

    /**
     * @inheritdoc
     */
    protected function getAssets()
    {
        $assets = parent::getAssets();

        $assets->addJs(function () {
            return $this->app->make('flarum.formatter')->getJs();
        });

        return $assets;
    }
}
