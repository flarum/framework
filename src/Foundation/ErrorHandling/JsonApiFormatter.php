<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Api\JsonApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobscure\JsonApi\Document;

class JsonApiFormatter implements HttpFormatter
{
    public function format(HandledError $error, Request $request): Response
    {
        $document = new Document;
        $document->setErrors([
            [
                'status' => (string) $error->getStatusCode(),
                'code' => $error->getType(),
            ],
        ]);

        return new JsonApiResponse($document, $error->getStatusCode());
    }
}
