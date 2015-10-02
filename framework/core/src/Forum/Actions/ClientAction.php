<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Flarum\Support\ClientAction as BaseClientAction;
use Flarum\Support\ClientView;
use Flarum\Api\Client;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Locale\LocaleManager;
use Flarum\Events\BuildForumClientView;

class ClientAction extends BaseClientAction
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'forum';

    /**
     * {@inheritdoc}
     */
    protected $translationKeys = ['core.forum'];

    /**
     * {@inheritdoc}
     */
    public function __construct(Client $apiClient, LocaleManager $locales, SettingsRepository $settings)
    {
        parent::__construct($apiClient, $locales, $settings);

        $this->layout = __DIR__.'/../../../views/forum.blade.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function fireEvent(ClientView $view, array &$keys)
    {
        event(new BuildForumClientView($this, $view, $keys));
    }

    /**
     * @inheritdoc
     */
    protected function getAssets()
    {
        $assets = parent::getAssets();

        // Add the formatter JavaScript payload.
        $assets->addJs(function () {
            return app('flarum.formatter')->getJS();
        });

        return $assets;
    }
}
