<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Tobscure\JsonApi\Document;

class JsonApiResponse extends JsonResponse
{
    public function __construct(Document $document, $status = 200, array $headers = [], $encodingOptions = 15)
    {
        $headers['content-type'] = 'application/vnd.api+json';

        parent::__construct($document, $status, $headers, $encodingOptions);
    }
}
