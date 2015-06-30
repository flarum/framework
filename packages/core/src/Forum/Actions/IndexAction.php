<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Assets\AssetManager;
use Flarum\Assets\JsCompiler;
use Flarum\Assets\LessCompiler;
use Flarum\Core;
use Flarum\Forum\Events\RenderView;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;
use Flarum\Support\Actor;
use Flarum\Support\HtmlAction;
use Illuminate\Database\ConnectionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class IndexAction extends HtmlAction
{
    protected $apiClient;

    protected $actor;

    protected $session;

    protected $database;

    public static $translations = [];

    public function __construct(Client $apiClient, Actor $actor, ConnectionInterface $database, SessionInterface $session)
    {
        $this->apiClient = $apiClient;
        $this->actor = $actor;
        $this->session = $session;
        $this->database = $database;
    }

    public function render(Request $request, $params = [])
    {
        $config = $this->database->table('config')
            ->whereIn('key', ['base_url', 'api_url', 'forum_title', 'welcome_title', 'welcome_message', 'theme_primary_color'])
            ->lists('value', 'key');
        $session = [];
        $alert = $this->session->get('alert');

        $response = $this->apiClient->send('Flarum\Api\Actions\Forum\ShowAction');

        $data = [$response->data];
        if (isset($response->included)) {
            $data = array_merge($data, $response->included);
        }

        if (($user = $this->actor->getUser()) && $user->exists) {
            $session = [
                'userId' => $user->id,
                'token' => $request->getCookieParams()['flarum_remember'],
            ];

            // TODO: calling on the API here results in an extra query to get
            // the user + their groups, when we already have this information on
            // $this->actor. Can we simply run the CurrentUserSerializer
            // manually?
            $response = $this->apiClient->send('Flarum\Api\Actions\Users\ShowAction', ['id' => $user->id]);

            $data[] = $response->data;
            if (isset($response->included)) {
                $data = array_merge($data, $response->included);
            }
        }

        $details = $this->getDetails($request, $params);

        $data = array_merge($data, array_get($details, 'data', []));
        $response = array_get($details, 'response');
        $title = array_get($details, 'title');

        $view = view('flarum.forum::index')
            ->with('title', ($title ? $title.' - ' : '').Core::config('forum_title'))
            ->with('config', $config)
            ->with('layout', 'flarum.forum::forum')
            ->with('data', $data)
            ->with('response', $response)
            ->with('session', $session)
            ->with('alert', $alert);

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

        $locale = $user->locale ?: Core::config('locale', 'en');

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

    protected function getDetails($request, $params)
    {
        $queryParams = $request->getQueryParams();

        // Only preload data if we're viewing the default index with no filters,
        // otherwise we have to do all kinds of crazy stuff
        if (!count($queryParams) && $request->getUri()->getPath() === '/') {
            $response = $this->apiClient->send('Flarum\Api\Actions\Discussions\IndexAction');

            return [
                'response' => $response
            ];
        }

        return [];
    }

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
