<?php namespace Flarum\Forum\Actions;

use Illuminate\Http\Request;
use Session;
use Auth;
use Cookie;
use Config;
use View;
use DB;
use Flarum\Forum\Events\RenderView;
use Flarum\Api\Request as ApiRequest;
use Flarum\Core;
use Flarum\Assets\AssetManager;
use Flarum\Assets\JsCompiler;
use Flarum\Assets\LessCompiler;
use Flarum\Locale\JsCompiler as LocaleJsCompiler;

class IndexAction extends BaseAction
{
    public static $translations = [];

    public function handle(Request $request, $params = [])
    {
        $config = DB::table('config')->whereIn('key', ['base_url', 'api_url', 'forum_title', 'welcome_title', 'welcome_message'])->lists('value', 'key');
        $data = [];
        $session = [];
        $alert = Session::get('alert');

        $response = app('Flarum\Api\Actions\Forum\ShowAction')
            ->handle(new ApiRequest([], $this->actor))
            ->content->toArray();

        $data = [$response['data']];
        if (isset($response['included'])) {
            $data = array_merge($data, $response['included']);
        }

        if (($user = $this->actor->getUser()) && $user->exists) {
            $session = [
                'userId' => $user->id,
                'token' => Cookie::get('flarum_remember')
            ];

            // TODO: calling on the API here results in an extra query to get
            // the user + their groups, when we already have this information on
            // $this->actor. Can we simply run the CurrentUserSerializer
            // manually?
            $response = app('Flarum\Api\Actions\Users\ShowAction')
                ->handle(new ApiRequest(['id' => $user->id], $this->actor))
                ->content->toArray();

            $data = array_merge($data, [$response['data']]);
            if (isset($response['included'])) {
                $data = array_merge($data, $response['included']);
            }
        }

        $view = View::make('flarum.forum::index')
            ->with('title', Core::config('forum_title'))
            ->with('config', $config)
            ->with('layout', 'flarum.forum::forum')
            ->with('data', $data)
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
