<?php namespace Flarum\Admin\Actions;

use Flarum\Support\Action;
use Illuminate\Http\Request;
use Session;
use Auth;
use Cookie;
use Config;
use View;
use DB;
use Flarum\Api\Request as ApiRequest;

class IndexAction extends Action
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

        $view = View::make('flarum.admin::index')
            ->with('title', 'Administration - '.Config::get('flarum::forum_title', 'Flarum Demo Forum'))
            ->with('config', $config)
            ->with('layout', 'flarum.admin::admin')
            ->with('data', $data)
            ->with('session', $session)
            ->with('alert', $alert);

        $assetManager = app('flarum.admin.assetManager');
        $root = __DIR__.'/../../..';
        $assetManager->addFile([
            $root.'/js/admin/dist/app.js',
            $root.'/less/admin/app.less'
        ]);

        // event(new RenderView($view, $assetManager, $this));

        return $view
            ->with('styles', $assetManager->getCSSFiles())
            ->with('scripts', $assetManager->getJSFiles());
    }
}
