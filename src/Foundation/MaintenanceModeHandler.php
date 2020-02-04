<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Document;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class MaintenanceModeHandler implements RequestHandlerInterface
{
    const MESSAGE = 'Currently down for maintenance. Please come back later.';

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Special handling for API requests: they get a proper API response
        if ($this->isApiRequest($request)) {
            return $this->apiResponse();
        }

        // By default, return a simple text message.
        return new HtmlResponse(self::MESSAGE, 503);
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        return Str::contains(
            $request->getHeaderLine('Accept'),
            'application/vnd.api+json'
        );
    }

    private function apiResponse(): ResponseInterface
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
