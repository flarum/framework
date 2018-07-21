<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Asset;

use Flarum\Frontend\Asset\AssetInterface;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Settings\SettingsRepositoryInterface;

class CustomCss implements AssetInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function css(SourceCollector $sources)
    {
        $sources->addString(function () {
            return $this->settings->get('custom_less');
        });
    }

    public function js(SourceCollector $sources)
    {
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
    }
}
