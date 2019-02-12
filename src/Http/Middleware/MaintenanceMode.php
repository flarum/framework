<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\Event\RenderMaintenancePage;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Zend\Diactoros\Response\HtmlResponse;

class MaintenanceMode implements Middleware
{
    /**
     * @var bool
     */
    private $maintenance;
    /**
     * @var Factory
     */
    private $view;

    public function __construct(bool $maintenance, Factory $view)
    {
        $this->maintenance = $maintenance;
        $this->view = $view;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($this->maintenance && $this->isApiRequest($request)) {
            return $this->apiResponse();
        }

        if ($this->maintenance) {
            $template = 'flarum.forum::frontend.maintenance';
            $status = 503;

            event(new RenderMaintenancePage($template, $status));

            $view = $this->view->make($template);

            return new HtmlResponse($view->render(), $status);
        }

        return $handler->handle($request);
    }

    private function isApiRequest(Request $request): bool
    {
        return str_contains(
            $request->getHeaderLine('Accept'),
            'application/vnd.api+json'
        );
    }

    private function apiResponse(): Response
    {
        return new JsonResponse(
            (new Document)->setErrors([
                'status' => '503',
                'title' => self::MESSAGE
            ]),
            503,
            ['Content-Type' => 'application/vnd.api+json']
        );
    }
}
