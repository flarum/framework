<?php namespace Flarum\Forum\Actions;

use Illuminate\Http\Request;
use Session;
use Auth;
use Cookie;
use Config;
use View;
use DB;
use Flarum\Forum\Events\RenderView;

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

            $response = $this->callAction('Flarum\Api\Actions\Users\ShowAction', ['id' => $user->id]);
            $response = $response->getData();

            $data = [$response->data];
            if (isset($response->included)) {
                $data = array_merge($data, $response->included);
            }
        }

        $view = View::make('flarum.forum::index')
            ->with('title', Config::get('flarum::forum_title', 'Flarum Demo Forum'))
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

        event(new RenderView($view, $assetManager));

        return $view
            ->with('styles', $assetManager->getCSSFiles())
            ->with('scripts', $assetManager->getJSFiles());
    }
}
