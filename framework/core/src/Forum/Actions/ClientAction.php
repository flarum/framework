<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Assets\AssetManager;
use Flarum\Assets\JsCompiler;
use Flarum\Assets\LessCompiler;
use Flarum\Core;
use Flarum\Core\Users\User;
use Flarum\Forum\Events\RenderView;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;
use Flarum\Locale\LocaleManager;
use Flarum\Support\ClientView;
use Flarum\Support\HtmlAction;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class ClientAction extends HtmlAction
{
    /**
     * @var Client
     */
    protected $apiClient;

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
     * @param Request $request
     * @param array $routeParams
     * @return \Flarum\Support\ClientView
     */
    public function render(Request $request, array $routeParams = [])
    {
        $actor = app('flarum.actor');

        $assets = $this->getAssets();
        $locale = $this->getLocaleCompiler($actor);

        $layout = 'flarum.forum::forum';

        $view = new ClientView(
            $request,
            $actor,
            $this->apiClient,
            $layout,
            $assets,
            $locale
        );

        return $view;
    }

    protected function getAssets()
    {
        $public = $this->getAssetDirectory();

        $assets = new AssetManager(
            new JsCompiler($public, 'forum.js'),
            new LessCompiler($public, 'forum.css')
        );

        $root = __DIR__.'/../../..';
        $assets->addFile($root.'/js/forum/dist/app.js');
        $assets->addFile($root.'/less/forum/app.less');

        foreach ($this->getLessVariables() as $name => $value) {
            $assets->addLess("@$name: $value;");
        }

        $assets->addLess(Core::config('custom_less'));

        return $assets;
    }

    protected function getLessVariables()
    {
        return [
            'fl-primary-color'   => Core::config('theme_primary_color', '#000'),
            'fl-secondary-color' => Core::config('theme_secondary_color', '#000'),
            'fl-dark-mode'       => Core::config('theme_dark_mode') ? 'true' : 'false',
            'fl-colored-header'  => Core::config('theme_colored_header') ? 'true' : 'false'
        ];
    }

    protected function getLocaleCompiler(User $actor)
    {
        $locale = $actor->locale ?: Core::config('locale', 'en');

//        $translations = $this->locales->getTranslations($locale);
        $jsFiles = $this->locales->getJsFiles($locale);

        $compiler = new LocaleJsCompiler($this->getAssetDirectory(), 'locale-'.$locale.'.js');
//        $compiler->setTranslations(static::filterTranslations($translations));
        array_walk($jsFiles, [$compiler, 'addFile']);

        return $compiler;
    }

    protected function getAssetDirectory()
    {
        return public_path().'/assets';
    }

    /**
     * @param $translations
     * @return array
     */
//    protected static function filterTranslations($translations)
//    {
//        $filtered = [];
//
//        foreach (static::$translations as $key) {
//            $parts = explode('.', $key);
//            $level = &$filtered;
//
//            foreach ($parts as $part) {
//                $level = &$level[$part];
//            }
//
//            $level = array_get($translations, $key);
//        }
//
//        return $filtered;
//    }
}
