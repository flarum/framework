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
use Flarum\Formatter\Formatter;
use Flarum\Foundation\Application;
use Flarum\Http\Controller\AbstractClientController;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
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
    protected $translations = '/^[^\.]+\.(?:forum|lib)\./';

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Application $app,
        Client $api,
        LocaleManager $locales,
        SettingsRepositoryInterface $settings,
        Dispatcher $events,
        Repository $cache,
        Formatter $formatter
    ) {
        parent::__construct($app, $api, $locales, $settings, $events, $cache);

        $this->layout = __DIR__.'/../../../views/forum.blade.php';
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssets()
    {
        $assets = parent::getAssets();

        $assets->addJs(function () {
            return $this->formatter->getJs();
        });

        return $assets;
    }
}
