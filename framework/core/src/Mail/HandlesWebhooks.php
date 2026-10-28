<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;

/**
 * An optional capability for {@see DriverInterface} implementations whose
 * provider can push delivery events (bounces, complaints) to Flarum via an
 * inbound webhook.
 *
 * This is intentionally a separate interface, not part of DriverInterface, so
 * that adding bounce handling never breaks third-party drivers that do not
 * implement it.
 *
 * @public
 */
interface HandlesWebhooks
{
    /**
     * The Symfony request parser for this provider. The parser is fully
     * responsible for **authenticating** the request and normalising the
     * payload into a {@see \Symfony\Component\RemoteEvent\RemoteEvent}.
     *
     * Verification is provider-specific and lives entirely in the parser — it
     * may check an HMAC signature (Mailgun), an IP allowlist (Postmark, Brevo),
     * or an SNS certificate (SES). Callers MUST NOT attempt to verify the
     * request themselves; they should call {@see RequestParserInterface::parse()}
     * and treat a thrown {@see \Symfony\Component\Webhook\Exception\RejectWebhookException}
     * as a rejection, and a `null` return as "verified but no actionable event"
     * (e.g. an SES/SNS subscription-confirmation handshake).
     */
    public function getWebhookRequestParser(SettingsRepositoryInterface $settings): RequestParserInterface;

    /**
     * The secret passed to the parser's `parse()` call, if this provider uses
     * one. Return null when the provider authenticates by other means (e.g.
     * Postmark's IP allowlist) — this is a valid, fully-supported state, NOT a
     * misconfiguration. Providers that require a secret (e.g. Mailgun) must
     * return false from webhookConfigured() while it is missing, so the request
     * is refused before it reaches the parser.
     */
    public function getWebhookSigningSecret(SettingsRepositoryInterface $settings): ?string;

    /**
     * Whether inbound webhooks can actually be received and trusted with the
     * current settings. Drivers that require configuration the admin must
     * supply (e.g. Mailgun's signing key) should return false until it is set,
     * so the UI can warn that no bounces will arrive. Drivers that need no such
     * config (e.g. Postmark, verified by IP) should simply return true.
     */
    public function webhookConfigured(SettingsRepositoryInterface $settings): bool;

    /**
     * Whether this driver can report bounces at all with the current settings.
     * Return false for a configuration that never delivers mail to real
     * recipients, such as a sandbox mode, and Flarum hides bounce handling
     * entirely instead of warning that it is not set up.
     */
    public function webhooksAvailable(SettingsRepositoryInterface $settings): bool;

    /**
     * Whether a bounce or drop the parser reported is permanent. Flarum only
     * flags the recipient when it is. Many providers report soft bounces as
     * bounces too, so check the provider's own payload, available from
     * $event->getPayload().
     */
    public function isPermanentFailure(MailerDeliveryEvent $event): bool;
}
