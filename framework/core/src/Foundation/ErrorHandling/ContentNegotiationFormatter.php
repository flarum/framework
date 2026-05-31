<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class ContentNegotiationFormatter implements HttpFormatter
{
    public function __construct(
        private HttpFormatter $jsonFormatter,
        private HttpFormatter $htmlFormatter,
    ) {
    }

    public function format(HandledError $error, Request $request): Response
    {
        /**
         * If this request is an API request, and we got an error,
         * return a JSON response instead of HTML.
         */
        if (RequestUtil::isApiRequest($request)) {
            return $this->jsonFormatter->format($error, $request);
        }

        return $this->htmlFormatter->format($error, $request);
    }
}
