<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Core;
use Flarum\Support\Actor;
use Flarum\Support\HtmlAction;
use Flarum\Forum\Events\RenderView;
use Psr\Http\Message\ServerRequestInterface as Request;
use Session;
use DB;

class IndexAction extends HtmlAction
{
    protected $apiClient;

    protected $actor;

    public function __construct(Client $apiClient, Actor $actor)
    {
        $this->apiClient = $apiClient;
        $this->actor = $actor;
    }

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

            $response = $this->apiClient->send('Flarum\Api\Actions\Users\ShowAction', ['id' => $user->id]);

            $data = [$response->data];
            if (isset($response->included)) {
                $data = array_merge($data, $response->included);
            }
        }

        $view = view('flarum.forum::index')
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
