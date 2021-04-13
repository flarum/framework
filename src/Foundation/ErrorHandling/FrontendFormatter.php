<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Frontend\Controller;
use Flarum\Frontend\Frontend;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This formatter will route certain errors to the SPA frontend.
 */
class FrontendFormatter implements HttpFormatter
{
    /**
     * @var Frontend
     */
    protected $frontend;

    public function __construct(Frontend $frontend)
    {
        $this->frontend = $frontend;
    }

    public function format(HandledError $error, Request $request): Response
    {
        $contentClass = $error->contentClass();
        /** @var callable */
        $content = new $contentClass;
        $this->frontend->content($content);

        return (new Controller($this->frontend))->handle($request)->withStatus($error->getStatusCode());
    }
}
