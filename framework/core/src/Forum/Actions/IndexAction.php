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

class IndexAction extends BaseAction
{
    public function handle(Request $request, $params = [])
    {
        $config = DB::table('config')->whereIn('key', ['base_url', 'api_url', 'forum_title', 'welcome_title', 'welcome_message'])->lists('value', 'key');
        $data = [];
        $session = [];
        $alert = Session::get('alert');

        if (($user = $this->actor->getUser()) && $user->exists) {
            $session = [
                'userId' => $user->id,
                'token' => Cookie::get('flarum_remember')
            ];

            $response = app('Flarum\Api\Actions\Users\ShowAction')
                ->handle(new ApiRequest(['id' => $user->id], $this->actor))
                ->content->toArray();

            $data = [$response['data']];
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

        $assetManager = app('flarum.forum.assetManager');
        $root = __DIR__.'/../../..';
        $assetManager->addFile([
            $root.'/js/forum/dist/app.js',
            $root.'/less/forum/app.less'
        ]);
        $assetManager->addLess('
            @fl-primary-color: '.Core::config('theme_primary_color').';
            @fl-secondary-color: '.Core::config('theme_secondary_color').';
            @fl-dark-mode: '.(Core::config('theme_dark_mode') ? 'true' : 'false').';
            @fl-colored_header: '.(Core::config('theme_colored_header') ? 'true' : 'false').';
        ');

        event(new RenderView($view, $assetManager, $this));

        return $view
            ->with('styles', $assetManager->getCSSFiles())
            ->with('scripts', $assetManager->getJSFiles());
    }
}
