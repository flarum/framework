<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Assets\AssetManager;
use Flarum\Assets\JsCompiler;
use Flarum\Assets\LessCompiler;
use Flarum\Core;
use Flarum\Forum\Events\RenderView;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;
use Flarum\Support\HtmlAction;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexAction extends HtmlAction
{
    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @var ConnectionInterface
     */
    protected $database;

    /**
     * @var array
     */
    public static $translations = [];

    /**
     * @param Client $apiClient
     * @param ConnectionInterface $database
     */
    public function __construct(Client $apiClient, ConnectionInterface $database)
    {
        $this->apiClient = $apiClient;
        $this->database = $database;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Illuminate\Contracts\View\View
     */
    public function render(Request $request, array $routeParams = [])
    {
        $config = $this->database->table('config')
            ->whereIn('key', ['base_url', 'api_url', 'forum_title', 'welcome_title', 'welcome_message', 'theme_primary_color'])
            ->lists('value', 'key');
        $session = [];

        $actor = app('flarum.actor');

        $response = $this->apiClient->send($actor, 'Flarum\Api\Actions\Forum\ShowAction');

        $data = [$response->data];
        if (isset($response->included)) {
            $data = array_merge($data, $response->included);
        }

        if ($actor->exists) {
            $session = [
                'userId' => $actor->id,
                'token' => $request->getCookieParams()['flarum_remember'],
            ];

            // TODO: calling on the API here results in an extra query to get
            // the user + their groups, when we already have this information on
            // $this->actor. Can we simply run the CurrentUserSerializer
            // manually?
            $response = $this->apiClient->send($actor, 'Flarum\Api\Actions\Users\ShowAction', ['id' => $actor->id]);

            $data[] = $response->data;
            if (isset($response->included)) {
                $data = array_merge($data, $response->included);
            }
        }

        $details = $this->getDetails($request, $routeParams);

        $data = array_merge($data, array_get($details, 'data', []));
        $response = array_get($details, 'response');
        $title = array_get($details, 'title');

        $view = view('flarum.forum::index')
            ->with('title', ($title ? $title.' - ' : '').Core::config('forum_title'))
            ->with('config', $config)
            ->with('layout', 'flarum.forum::forum')
            ->with('data', $data)
            ->with('response', $response)
            ->with('session', $session);

        $root = __DIR__.'/../../..';
        $public = public_path().'/assets';

        $assets = new AssetManager(
            new JsCompiler($public, 'forum.js'),
            new LessCompiler($public, 'forum.css')
        );

        $assets->addFile($root.'/js/forum/dist/app.js');
        $assets->addFile($root.'/less/forum/app.less');

        $variables = [
            'fl-primary-color'   => Core::config('theme_primary_color', '#000'),
            'fl-secondary-color' => Core::config('theme_secondary_color', '#000'),
            'fl-dark-mode'       => Core::config('theme_dark_mode') ? 'true' : 'false',
            'fl-colored-header'  => Core::config('theme_colored_header') ? 'true' : 'false'
        ];
        foreach ($variables as $name => $value) {
            $assets->addLess("@$name: $value;");
        }

        $locale = $actor->locale ?: Core::config('locale', 'en');

        $localeManager = app('flarum.localeManager');
        $translations = $localeManager->getTranslations($locale);
        $jsFiles = $localeManager->getJsFiles($locale);

        $localeCompiler = new LocaleJsCompiler($public, 'locale-'.$locale.'.js');
        $localeCompiler->setTranslations(static::filterTranslations($translations));
        array_walk($jsFiles, [$localeCompiler, 'addFile']);

        event(new RenderView($view, $assets, $this));

        return $view
            ->with('styles', [$assets->getCssFile()])
            ->with('scripts', [$assets->getJsFile(), $localeCompiler->getFile()]);
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return array
     */
    protected function getDetails(Request $request, array $routeParams)
    {
        $queryParams = $request->getQueryParams();

        // Only preload data if we're viewing the default index with no filters,
        // otherwise we have to do all kinds of crazy stuff
        if (!count($queryParams) && $request->getUri()->getPath() === '/') {
            $response = $this->apiClient->send(app('flarum.actor'), 'Flarum\Api\Actions\Discussions\IndexAction');

            return [
                'response' => $response
            ];
        }

        return [];
    }

    /**
     * @param $translations
     * @return array
     */
    protected static function filterTranslations($translations)
    {
        $filtered = [];

        foreach (static::$translations as $key) {
            $parts = explode('.', $key);
            $level = &$filtered;

            foreach ($parts as $part) {
                if (! isset($level[$part])) {
                    $level[$part] = [];
                }

                $level = &$level[$part];
            }

            $level = array_get($translations, $key);
        }

        return $filtered;
    }
}
