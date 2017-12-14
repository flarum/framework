<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Formatter\Formatter;
use Flarum\Frontend\AbstractFrontend;
use Flarum\Frontend\FrontendAssetsFactory;
use Flarum\Frontend\FrontendViewFactory;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;

class Frontend extends AbstractFrontend
{
    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        FrontendAssetsFactory $assets,
        FrontendViewFactory $view,
        SettingsRepositoryInterface $settings,
        LocaleManager $locales,
        Formatter $formatter
    ) {
        parent::__construct($assets, $view, $settings, $locales);

        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        $view = parent::getView();

        $view->getJs()->addString(function () {
            return $this->formatter->getJs();
        });

        $view->getCss()->addString(function () {
            return $this->settings->get('custom_less');
        });

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'forum';
    }
}
