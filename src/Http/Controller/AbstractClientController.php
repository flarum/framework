<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Controller;

use Flarum\Api\Client;
use Flarum\Asset\AssetManager;
use Flarum\Asset\JsCompiler;
use Flarum\Asset\LessCompiler;
use Flarum\Event\ConfigureClientView;
use Flarum\Foundation\Application;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
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
abstract class AbstractClientController extends AbstractHtmlController
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
     * A regex matching the keys of the translations that should be included in
     * the compiled locale file.
     *
     * @var string
     */
    protected $translations;

    /**
     * @var \Flarum\Foundation\Application
     */
    protected $app;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var \Flarum\Settings\SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @param \Flarum\Foundation\Application $app
     * @param Client $api
     * @param LocaleManager $locales
     * @param \Flarum\Settings\SettingsRepositoryInterface $settings
     * @param Dispatcher $events
     * @param Repository $cache
     */
    public function __construct(
        Application $app,
        Client $api,
        LocaleManager $locales,
        SettingsRepositoryInterface $settings,
        Dispatcher $events,
        Repository $cache
    ) {
        $this->app = $app;
        $this->api = $api;
        $this->locales = $locales;
        $this->settings = $settings;
        $this->events = $events;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     *
     * @return ClientView
     */
    public function render(Request $request)
    {
        $actor = $request->getAttribute('actor');
        $assets = $this->getAssets();
        $locale = $this->locales->getLocale();
        $localeCompiler = $locale ? $this->getLocaleCompiler($locale) : null;

        $view = new ClientView(
            $this->api,
            $request,
            $actor,
            $assets,
            $this->layout,
            $localeCompiler
        );

        $view->setVariable('locales', $this->locales->getLocales());
        $view->setVariable('locale', $locale);

        $this->events->fire(
            new ConfigureClientView($this, $view)
        );

        if ($localeCompiler) {
            $translations = array_get($this->locales->getTranslator()->getMessages(), 'messages', []);

            $translations = $this->filterTranslations($translations);

            $localeCompiler->setTranslations($translations);
        }

        app('view')->share('translator', $this->locales->getTranslator());

        return $view;
    }

    /**
     * Flush the client's assets so that they will be regenerated from scratch
     * on the next render.
     */
    public function flushAssets()
    {
        $this->flushCss();
        $this->flushJs();
    }

    public function flushCss()
    {
        $this->getAssets()->flushCss();
    }

    public function flushJs()
    {
        $this->getAssets()->flushJs();

        $this->flushLocales();
    }

    public function flushLocales()
    {
        $locales = array_keys($this->locales->getLocales());

        foreach ($locales as $locale) {
            $this->getLocaleCompiler($locale)->flush();
        }
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
        $watch = $this->app->config('debug');

        $assets = new AssetManager(
            new JsCompiler($public, "$this->clientName.js", $watch, $this->cache),
            new LessCompiler($public, "$this->clientName.css", $watch, $this->app->storagePath().'/less')
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
        $root = __DIR__.'/../../..';

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
        $compiler = new LocaleJsCompiler(
            $this->getAssetDirectory(),
            "$this->clientName-$locale.js",
            $this->app->config('debug'),
            $this->cache
        );

        foreach ($this->locales->getJsFiles($locale) as $file) {
            $compiler->addFile($file);
        }

        return $compiler;
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
     * @return array
     */
    protected function filterTranslations(array $translations)
    {
        if (! $this->translations) {
            return [];
        }

        $filtered = array_filter(array_keys($translations), function ($id) {
            return preg_match($this->translations, $id);
        });

        return array_only($translations, $filtered);
    }
}
