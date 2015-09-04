<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Support;

use Flarum\Api\Client;
use Flarum\Assets\AssetManager;
use Flarum\Assets\JsCompiler;
use Flarum\Assets\LessCompiler;
use Flarum\Core;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Core\Users\User;
use Flarum\Events\BuildClientView;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;
use Flarum\Locale\LocaleManager;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This action sets up a ClientView, and preloads it with the assets necessary
 * to boot a Flarum client.
 *
 * Subclasses should set a $clientName, $layout, and $translationKeys. The
 * client name will be used to locate the client assets (or alternatively,
 * subclasses can overwrite the addAssets method), and set up asset compilers
 * which write to the assets directory. Configured LESS customizations will be
 * appended.
 *
 * A locale compiler is set up for the actor's locale, including the
 * translations specified in $translationKeys. Additionally, an event is fired
 * before the ClientView is returned, giving extensions an opportunity to add
 * assets, translations, or alter the view.
 */
abstract class ClientAction extends HtmlAction
{
    /**
     * The name of the client. This is used to locate assets within the js/
     * and less/ directories. It is also used as the filename of the compiled
     * asset files.
     *
     * @var string
     */
    protected $clientName;

    /**
     * The name of the view to include as the page layout.
     *
     * @var string
     */
    protected $layout;

    /**
     * The keys of the translations that should be included in the compiled
     * locale file.
     *
     * @var array
     */
    protected $translationKeys = [];

    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var SettingsRepository
     */
    protected $settings;

    /**
     * @param Client $apiClient
     * @param LocaleManager $locales
     * @param SettingsRepository $settings
     */
    public function __construct(Client $apiClient, LocaleManager $locales, SettingsRepository $settings)
    {
        $this->apiClient = $apiClient;
        $this->locales = $locales;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     *
     * @return ClientView
     */
    public function render(Request $request, array $routeParams = [])
    {
        $actor = app('flarum.actor');
        $assets = $this->getAssets();
        $locale = $this->getLocale($actor, $request);
        $localeCompiler = $this->getLocaleCompiler($locale);

        $view = new ClientView(
            $this->apiClient,
            $request,
            $actor,
            $assets,
            $localeCompiler,
            $this->layout
        );

        $view->setVariable('locales', $this->locales->getLocales());
        $view->setVariable('locale', $locale);

        // Now that we've set up the ClientView instance, we can fire an event
        // to give extensions the opportunity to add their own assets and
        // translations. We will pass an array to the event which specifies
        // which translations should be included in the locale file. Afterwards,
        // we will filter all of the translations for the actor's locale and
        // compile only the ones we need.
        $translations = $this->locales->getTranslations($locale);
        $keys = $this->translationKeys;

        event(new BuildClientView($this, $view, $keys));

        $translations = $this->filterTranslations($translations, $keys);

        $localeCompiler->setTranslations($translations);

        return $view;
    }

    /**
     * Flush the client's assets so that they will be regenerated from scratch
     * on the next render.
     *
     * @return void
     */
    public function flushAssets()
    {
        $this->getAssets()->flush();
    }

    /**
     * Set up the asset manager, preloaded with a JavaScript compiler and a LESS
     * compiler. Automatically add the files necessary to boot a Flarum client,
     * as well as any configured LESS customizations.
     *
     * @return AssetManager
     */
    protected function getAssets()
    {
        $public = $this->getAssetDirectory();

        $assets = new AssetManager(
            new JsCompiler($public, "$this->clientName.js"),
            new LessCompiler($public, "$this->clientName.css")
        );

        $this->addAssets($assets);
        $this->addCustomizations($assets);

        return $assets;
    }

    /**
     * Add the assets necessary to boot a Flarum client, found within the
     * directory specified by the $clientName property.
     *
     * @param AssetManager $assets
     */
    protected function addAssets(AssetManager $assets)
    {
        $root = __DIR__.'/../..';

        $assets->addFile("$root/js/$this->clientName/dist/app.js");
        $assets->addFile("$root/less/$this->clientName/app.less");
    }

    /**
     * Add any configured JS/LESS customizations to the asset manager.
     *
     * @param AssetManager $assets
     */
    protected function addCustomizations(AssetManager $assets)
    {
        $assets->addLess(function () {
            $less = '';

            foreach ($this->getLessVariables() as $name => $value) {
                $less .= "@$name: $value;";
            }

            $less .= $this->settings->get('custom_less');

            return $less;
        });
    }

    /**
     * Get the values of any LESS variables to compile into the CSS, based on
     * the forum's configuration.
     *
     * @return array
     */
    protected function getLessVariables()
    {
        return [
            'config-primary-color'   => $this->settings->get('theme_primary_color') ?: '#000',
            'config-secondary-color' => $this->settings->get('theme_secondary_color') ?: '#000',
            'config-dark-mode'       => $this->settings->get('theme_dark_mode') ? 'true' : 'false',
            'config-colored-header'  => $this->settings->get('theme_colored_header') ? 'true' : 'false'
        ];
    }

    /**
     * Set up the locale compiler for the given locale.
     *
     * @param string $locale
     * @return LocaleJsCompiler
     */
    protected function getLocaleCompiler($locale)
    {
        $compiler = new LocaleJsCompiler($this->getAssetDirectory(), "$this->clientName-$locale.js");

        foreach ($this->locales->getJsFiles($locale) as $file) {
            $compiler->addFile($file);
        }

        return $compiler;
    }

    /**
     * Get the name of the locale to use.
     *
     * @param User $actor
     * @param Request $request
     * @return string
     */
    protected function getLocale(User $actor, Request $request)
    {
        if ($actor->exists) {
            $locale = $actor->getPreference('locale');
        } else {
            $locale = array_get($request->getCookieParams(), 'locale');
        }

        if (! $locale || ! $this->locales->hasLocale($locale)) {
            $locale = $this->settings->get('default_locale', 'en');
        }

        if (! $this->locales->hasLocale($locale)) {
            return 'en';
        }

        return $locale;
    }

    /**
     * Get the path to the directory where assets should be written.
     *
     * @return string
     */
    protected function getAssetDirectory()
    {
        return public_path().'/assets';
    }

    /**
     * Take a selection of keys from a collection of translations.
     *
     * @param array $translations
     * @param array $keys
     * @return array
     */
    protected function filterTranslations(array $translations, array $keys)
    {
        $filtered = [];

        foreach ($keys as $key) {
            $parts = explode('.', $key);
            $level = &$filtered;

            foreach ($parts as $part) {
                $level = &$level[$part];
            }

            $level = array_get($translations, $key);
        }

        return $filtered;
    }
}
