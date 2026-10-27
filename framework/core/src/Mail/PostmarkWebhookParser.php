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
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;

class PostmarkWebhookParser implements RequestParserInterface
{
    public function __construct(
        protected RequestParserInterface $parser
    ) {
    }

    public function parse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent|array|null
    {
        $event = $this->parser->parse($request, $secret);

        if ($event instanceof MailerDeliveryEvent
            && $event->getName() === MailerDeliveryEvent::BOUNCE
            && ($event->getPayload()['Inactive'] ?? false) !== true) {
            return null;
        }

        return $event;
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
