<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Mail\Event\EmailBounced;
use Flarum\Mail\Event\EmailComplained;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerEngagementEvent;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

/**
 * Receives inbound delivery-event webhooks (bounces, complaints) from the
 * active mail driver's provider, verifies them, and dispatches the
 * corresponding Flarum event so listeners can react.
 */
class WebhookController implements RequestHandlerInterface
{
    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        protected UserRepository $users,
        protected Dispatcher $events,
        protected LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $driver = $this->container->make('flarum.mail.configured_driver');

        // Only the active driver, and only if it can receive webhooks, is
        // allowed to process them. Anything else is a silent no-op.
        if (! $driver instanceof HandlesWebhooks) {
            return new EmptyResponse(404);
        }

        $requested = Arr::get($request->getQueryParams(), 'driver');
        $active = $this->settings->get('mail_driver');

        if ($requested !== null && $requested !== $active) {
            return new EmptyResponse(404);
        }

        $parser = $driver->getWebhookRequestParser($this->settings);
        $symfonyRequest = $this->toSymfonyRequest($request);

        // The parser owns ALL verification — HMAC signature (Mailgun), IP
        // allowlist (Postmark/Brevo), or SNS certificate (SES). We never verify
        // here, and we must NOT assume a secret is what authenticates: some
        // providers legitimately have no secret. Pass whatever the driver
        // supplies (or an empty string); drivers that require it enforce that
        // themselves and reject via RejectWebhookException.
        $secret = $driver->getWebhookSigningSecret($this->settings) ?? '';

        try {
            $event = $parser->parse($symfonyRequest, $secret);
        } catch (RejectWebhookException $e) {
            return new EmptyResponse($e->getStatusCode());
        }

        // A null return means the request was verified and handled but carried
        // no event we act on — e.g. an SES/SNS subscription-confirmation
        // handshake, or an event type we ignore. Acknowledge with a 200 either
        // way so the provider stops retrying.
        if ($event !== null) {
            foreach (is_array($event) ? $event : [$event] as $remoteEvent) {
                $this->handleEvent($remoteEvent);
            }
        }

        return new EmptyResponse(200);
    }

    protected function handleEvent(object $event): void
    {
        $email = null;
        $reason = '';

        if ($event instanceof MailerDeliveryEvent) {
            // Only permanent failures suppress. DEFERRED (transient),
            // DELIVERED and RECEIVED are ignored.
            if (! in_array($event->getName(), [MailerDeliveryEvent::BOUNCE, MailerDeliveryEvent::DROPPED], true)) {
                return;
            }

            $email = $event->getRecipientEmail();
            $reason = $event->getReason();
            $recipient = $email ? $this->users->findByEmail($email) : null;

            $this->events->dispatch(new EmailBounced($email, $reason, $recipient, $event->getPayload()));

            return;
        }

        if ($event instanceof MailerEngagementEvent && $event->getName() === MailerEngagementEvent::SPAM) {
            $email = $event->getRecipientEmail();
            $recipient = $email ? $this->users->findByEmail($email) : null;

            $this->events->dispatch(new EmailComplained($email, 'complaint', $recipient, $event->getPayload()));
        }
    }

    /**
     * Build the Symfony HttpFoundation request the driver's parser expects,
     * preserving the method, headers and raw body needed for signature
     * verification. Avoids pulling in the PSR-7 <-> Symfony bridge dependency.
     */
    protected function toSymfonyRequest(ServerRequestInterface $request): SymfonyRequest
    {
        $server = [];

        foreach ($request->getHeaders() as $name => $values) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = implode(', ', $values);
        }

        $server['REQUEST_METHOD'] = $request->getMethod();
        $server['CONTENT_TYPE'] = $request->getHeaderLine('Content-Type');

        // Preserve the client IP so parsers that verify by IP allowlist
        // (e.g. Postmark, Brevo) can resolve it via Request::getClientIp().
        // Flarum's ProcessIp middleware stashes the resolved IP on the
        // 'ipAddress' attribute; fall back to the raw REMOTE_ADDR.
        $ip = $request->getAttribute('ipAddress')
            ?? Arr::get($request->getServerParams(), 'REMOTE_ADDR');

        if ($ip !== null) {
            $server['REMOTE_ADDR'] = $ip;
        }

        return new SymfonyRequest(
            query: $request->getQueryParams(),
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: $server,
            content: $this->rawBody($request),
        );
    }

    /**
     * The raw request body as a JSON string. Reads the body stream directly,
     * but falls back to re-encoding the already-parsed body if an upstream
     * middleware has consumed the (non-rewindable) stream first — which is
     * what happens inside Flarum's middleware stack.
     */
    protected function rawBody(ServerRequestInterface $request): string
    {
        $body = $request->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $content = (string) $body;

        if ($content === '' && is_array($parsed = $request->getParsedBody())) {
            $content = (string) json_encode($parsed);
        }

        return $content;
    }
}
