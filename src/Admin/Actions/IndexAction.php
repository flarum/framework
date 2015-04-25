<?php namespace Flarum\Admin\Actions;

use Flarum\Support\Action;
use Illuminate\Http\Request;
use Session;
use Auth;
use Cookie;
use Config;
use View;

class IndexAction extends Action
{
    public function handle(Request $request, $params = [])
    {
        $config = [
            'baseURL' => 'http://flarum.dev/admin',
            'apiURL' => 'http://flarum.dev/api',
            'forumTitle' => Config::get('flarum::forum_title', 'Flarum Demo Forum')
        ];
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

        return View::make('flarum.admin::index')
            ->with('title', 'Administration - '.Config::get('flarum::forum_title', 'Flarum Demo Forum'))
            ->with('styles', app('flarum.admin.assetManager')->getCSSFiles())
            ->with('scripts', app('flarum.admin.assetManager')->getJSFiles())
            ->with('config', $config)
            ->with('layout', View::make('flarum.admin::admin'))
            ->with('data', $data)
            ->with('session', $session)
            ->with('alert', $alert);
    }
}
