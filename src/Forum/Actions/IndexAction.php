<?php namespace Flarum\Forum\Actions;

use Flarum\Support\HtmlAction;
use Session;
use Auth;
use Config;
use DB;
use Flarum\Forum\Events\RenderView;
use Flarum\Api\Request as ApiRequest;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexAction extends HtmlAction
{
    public function render(Request $request, $params = [])
    {
        $config = DB::table('config')->whereIn('key', ['base_url', 'api_url', 'forum_title', 'welcome_title', 'welcome_message'])->lists('value', 'key');
        $data = [];
        $session = [];
        $alert = Session::get('alert');

        if (($user = $this->actor->getUser()) && $user->exists) {
            $session = [
                'userId' => $user->id,
                'token' => $request->getCookieParams()['flarum_remember'],
            ];

            $response = app('Flarum\Api\Actions\Users\ShowAction')
                ->handle(new ApiRequest(['id' => $user->id], $this->actor))
                ->content->toArray();

            $data = [$response['data']];
            if (isset($response['included'])) {
                $data = array_merge($data, $response['included']);
            }
        }

        $view = view('flarum.forum::index')
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

        event(new RenderView($view, $assetManager, $this));

        return $view
            ->with('styles', $assetManager->getCSSFiles())
            ->with('scripts', $assetManager->getJSFiles());
    }
}
