<?php namespace Flarum\Support;

use Flarum\Api\Client;
use Flarum\Assets\AssetManager;
use Flarum\Assets\JsCompiler;
use Flarum\Assets\LessCompiler;
use Flarum\Core;
use Flarum\Core\Users\User;
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
     * @param Client $apiClient
     * @param LocaleManager $locales
     */
    public function __construct(Client $apiClient, LocaleManager $locales)
    {
        $this->apiClient = $apiClient;
        $this->locales = $locales;
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
        $locale = $this->getLocaleCompiler($actor);

        $view = new ClientView(
            $this->apiClient,
            $request,
            $actor,
            $assets,
            $locale,
            $this->layout
        );

        // Now that we've set up the ClientView instance, we can fire an event
        // to give extensions the opportunity to add their own assets and
        // translations. We will pass an array to the event which specifies
        // which translations should be included in the locale file. Afterwards,
        // we will filter all of the translations for the actor's locale and
        // compile only the ones we need.
        $translations = $this->locales->getTranslations($actor->locale);
        $keys = $this->translationKeys;

        // TODO: event($this, $view, $keys)

        $translations = $this->filterTranslations($translations, $keys);

        $locale->setTranslations($translations);

        return $view;
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
        foreach ($this->getLessVariables() as $name => $value) {
            $assets->addLess("@$name: $value;");
        }

        $assets->addLess(Core::config('custom_less'));
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
            'fl-primary-color'   => Core::config('theme_primary_color', '#000'),
            'fl-secondary-color' => Core::config('theme_secondary_color', '#000'),
            'fl-dark-mode'       => Core::config('theme_dark_mode') ? 'true' : 'false',
            'fl-colored-header'  => Core::config('theme_colored_header') ? 'true' : 'false'
        ];
    }

    /**
     * Set up the locale compiler for the given user's locale.
     *
     * @param User $actor
     * @return LocaleJsCompiler
     */
    protected function getLocaleCompiler(User $actor)
    {
        $locale = $actor->locale;

        $compiler = new LocaleJsCompiler($this->getAssetDirectory(), "$this->clientName-$locale.js");

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
