<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class Controller implements RequestHandlerInterface
{
    /**
     * @var Frontend
     */
    protected $frontend;

    public function __construct(Frontend $frontend)
    {
        $this->frontend = $frontend;
    }

    public function handle(Request $request): Response
    {
        return new HtmlResponse(
            $this->frontend->document($request)->render()
        );
    }
}
