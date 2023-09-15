<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Controller;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractHtmlController extends AbstractController
{
    public function __invoke(Request $request): ResponseInterface
    {
        $view = $this->render($request);

        if ($view instanceof Renderable) {
            $view = $view->render();
        }

        return new HtmlResponse($view);
    }

    abstract protected function render(Request $request): Renderable|string;
}
