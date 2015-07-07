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

class IndexAction extends ClientAction
{
    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Illuminate\Contracts\View\View
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = parent::render($request, $routeParams);

        $queryParams = $request->getQueryParams();

        // Only preload data if we're viewing the default index with no filters,
        // otherwise we have to do all kinds of crazy stuff
        if (! count($queryParams) && $request->getUri()->getPath() === '/') {
            $actor = app('flarum.actor');
            $action = 'Flarum\Api\Actions\Discussions\IndexAction';

            $document = $this->apiClient->send($actor, $action)->getBody();

            $view->setDocument($document);
            $view->setContent(app('view')->make('flarum.forum::index', compact('document')));
        }

        return $view;
    }
}
