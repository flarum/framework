<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\MailWebhooks;
use Flarum\Mail\WebhookController;
use Flarum\Testing\unit\TestCase;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;

class WebhookRequestTest extends TestCase
{
    #[Test]
    public function parsers_receive_the_original_url_form_and_body(): void
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write('mandrill_events=%5B%5D');
        $request = (new ServerRequest([], [], 'http://internal/mail/webhook/mandrill?token=a%2Bb', 'POST'))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Authorization', 'Basic abc')
            ->withBody($body)
            ->withParsedBody(['mandrill_events' => '[]'])
            ->withQueryParams(['token' => 'a+b', 'driver' => 'mandrill'])
            ->withAttribute('ipAddress', '192.0.2.1');

        $webhooks = m::mock(MailWebhooks::class);
        $webhooks->shouldReceive('baseUrl')->andReturn('https://forum.example:8443/community');
        $controller = new class($webhooks) extends WebhookController {
            public function __construct(MailWebhooks $webhooks)
            {
                $this->webhooks = $webhooks;
            }

            public function convert(ServerRequestInterface $request): Request
            {
                return $this->toSymfonyRequest($request);
            }
        };
        $converted = $controller->convert($request);

        $this->assertSame('[]', $converted->request->get('mandrill_events'));
        $this->assertSame('https://forum.example:8443/community/mail/webhook/mandrill?token=a%2Bb', $converted->getUri());
        $this->assertSame('mandrill_events=%5B%5D', $converted->getContent());
        $this->assertSame('Basic abc', $converted->headers->get('Authorization'));
        $this->assertSame('192.0.2.1', $converted->getClientIp());
    }
}
