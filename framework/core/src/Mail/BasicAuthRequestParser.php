<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

class BasicAuthRequestParser implements RequestParserInterface
{
    public function __construct(
        protected RequestParserInterface $parser,
        #[\SensitiveParameter] protected ?string $credentials,
    ) {
    }

    public function parse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent|array|null
    {
        if (! $this->credentials || ! hash_equals('Basic '.base64_encode($this->credentials), $request->headers->get('Authorization', ''))) {
            throw new RejectWebhookException(403, 'Invalid credentials.');
        }

        return $this->parser->parse($request, $secret);
    }

    public function createSuccessfulResponse(): Response
    {
        return $this->parser->createSuccessfulResponse();
    }

    public function createRejectedResponse(string $reason): Response
    {
        return $this->parser->createRejectedResponse($reason);
    }
}
