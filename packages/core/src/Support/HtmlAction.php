<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Support;

use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;

abstract class HtmlAction extends Action
{
    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Zend\Diactoros\Response
     */
    public function handle(Request $request, array $routeParams = [])
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
    abstract protected function render(Request $request, array $routeParams = []);
}
