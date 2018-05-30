<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Controller;

use Illuminate\Contracts\Support\Renderable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

abstract class AbstractHtmlController implements RequestHandlerInterface
{
    /**
     * @param Request $request
     * @return HtmlResponse
     */
    public function handle(Request $request): ResponseInterface
    {
        $view = $this->render($request);

        if ($view instanceof Renderable) {
            $view = $view->render();
        }

        return new HtmlResponse($view);
    }

    /**
     * @param Request $request
     * @return string|Renderable
     */
    abstract protected function render(Request $request);
}
