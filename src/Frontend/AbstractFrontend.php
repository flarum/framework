<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;

abstract class AbstractFrontend
{
    /**
     * @var FrontendAssetsFactory
     */
    protected $assets;

    /**
     * @var FrontendViewFactory
     */
    protected $view;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param FrontendAssetsFactory $assets
     * @param FrontendViewFactory $view
     * @param SettingsRepositoryInterface $settings
     * @param LocaleManager $locales
     */
    public function __construct(FrontendAssetsFactory $assets, FrontendViewFactory $view, SettingsRepositoryInterface $settings, LocaleManager $locales)
    {
        $this->assets = $assets;
        $this->view = $view;
        $this->settings = $settings;
        $this->locales = $locales;
    }

    /**
     * @return FrontendView
     */
    public function getView()
    {
        $view = $this->view->make($this->getLayout(), $this->getAssets());

        $this->addDefaultAssets($view);
        $this->addCustomLess($view);
        $this->addTranslations($view);

        return $view;
    }

    /**
     * @return FrontendAssets
     */
    public function getAssets()
    {
        return $this->assets->make($this->getName());
    }

    /**
     * Get the name of the client.
     *
     * @return string
     */
    abstract protected function getName();

    /**
     * Get the path to the client layout view.
     *
     * @return string
     */
    protected function getLayout()
    {
        return 'flarum.forum::frontend.'.$this->getName();
    }

    /**
     * Get a regular expression to match against translation keys.
     *
     * @return string
     */
    protected function getTranslationFilter()
    {
        return '/^.+(?:\.|::)(?:'.$this->getName().'|lib)\./';
    }

    /**
     * @param FrontendView $view
     */
    private function addDefaultAssets(FrontendView $view)
    {
        $root = __DIR__.'/../..';
        $name = $this->getName();

        $view->getJs()->addFile("$root/js/dist/$name.js");
        $view->getCss()->addFile("$root/less/$name.less");
    }

    /**
     * @param FrontendView $view
     */
    private function addCustomLess(FrontendView $view)
    {
        $css = $view->getCss();
        $localeCss = $view->getLocaleCss();

        $lessVariables = function () {
            $less = '';

            foreach ($this->getLessVariables() as $name => $value) {
                $less .= "@$name: $value;";
            }

            return $less;
        };

        $css->addString($lessVariables);
        $localeCss->addString($lessVariables);
    }

    /**
     * Get the values of any LESS variables to compile into the CSS, based on
     * the forum's configuration.
     *
     * @return array
     */
    private function getLessVariables()
    {
        return [
            'config-primary-color'   => $this->settings->get('theme_primary_color') ?: '#000',
            'config-secondary-color' => $this->settings->get('theme_secondary_color') ?: '#000',
            'config-dark-mode'       => $this->settings->get('theme_dark_mode') ? 'true' : 'false',
            'config-colored-header'  => $this->settings->get('theme_colored_header') ? 'true' : 'false'
        ];
    }

    /**
     * @param FrontendView $view
     */
    private function addTranslations(FrontendView $view)
    {
        $translations = array_get($this->locales->getTranslator()->getCatalogue()->all(), 'messages', []);

        $translations = $this->filterTranslations($translations);

        $view->getLocaleJs()->setTranslations($translations);
    }

    /**
     * Take a selection of keys from a collection of translations.
     *
     * @param array $translations
     * @return array
     */
    private function filterTranslations(array $translations)
    {
        $filter = $this->getTranslationFilter();

        if (! $filter) {
            return [];
        }

        $filtered = array_filter(array_keys($translations), function ($id) use ($filter) {
            return preg_match($filter, $id);
        });

        return array_only($translations, $filtered);
    }
}
