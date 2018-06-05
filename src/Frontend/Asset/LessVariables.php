<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Settings\SettingsRepositoryInterface;

class LessVariables implements AssetInterface
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
        $this->addLessVariables($sources);
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
        $this->addLessVariables($sources);
    }

    private function addLessVariables(SourceCollector $compiler)
    {
        $vars = [
            'config-primary-color'   => $this->settings->get('theme_primary_color', '#000'),
            'config-secondary-color' => $this->settings->get('theme_secondary_color', '#000'),
            'config-dark-mode'       => $this->settings->get('theme_dark_mode') ? 'true' : 'false',
            'config-colored-header'  => $this->settings->get('theme_colored_header') ? 'true' : 'false'
        ];

        $compiler->addString(function () use ($vars) {
            return array_reduce(array_keys($vars), function ($string, $name) use ($vars) {
                return $string."@$name: {$vars[$name]};";
            }, '');
        });
    }

    public function js(SourceCollector $sources)
    {
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
    }
}
