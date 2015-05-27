<?php namespace Flarum\Support;

use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;

abstract class HtmlAction extends Action
{
    public function handle(Request $request, $routeParams = [])
    {
        $view = $this->render($request, $routeParams);

        $response = new Response();
        $response->getBody()->write($view->render());
        return $response;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Illuminate\Contracts\View\View
     */
    abstract protected function render(Request $request, $routeParams = []);
}
