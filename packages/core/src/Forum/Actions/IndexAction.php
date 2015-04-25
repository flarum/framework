<?php namespace Flarum\Forum\Actions;

use Illuminate\Http\Request;
use Session;
use Auth;
use Cookie;
use Config;
use View;

class IndexAction extends BaseAction
{
    public function handle(Request $request, $params = [])
    {
        $config = [
            'baseURL' => 'http://flarum.dev',
            'apiURL' => 'http://flarum.dev/api',
            'forumTitle' => Config::get('flarum::forum_title', 'Flarum Demo Forum'),
            'welcomeDescription' => 'Flarum is now at a point where you can have basic conversations, so here is a little demo for you to break. <a href="http://demo.flarum.org/#/1/welcome-to-the-first-public-demo-of-flarum">Learn more &raquo;</a>'
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

        return View::make('flarum.forum::index')
            ->with('title', Config::get('flarum::forum_title', 'Flarum Demo Forum'))
            ->with('styles', app('flarum.forum.assetManager')->getCSSFiles())
            ->with('scripts', app('flarum.forum.assetManager')->getJSFiles())
            ->with('config', $config)
            ->with('layout', View::make('flarum.forum::forum'))
            ->with('data', $data)
            ->with('session', $session)
            ->with('alert', $alert);
    }
}
