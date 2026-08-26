<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Message;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;

/**
 * The mail translator holds parameter values back as markers so they cannot be
 * parsed as markup, and they are put back once the mail is rendered. The values
 * must be restored on every mail — whichever template or extension produced it,
 * and whether or not that template routed the value through the formatter — so
 * that a reader never sees a raw `flarumsafevalue…endflarumsafevalue` marker.
 *
 * These send a real mail through the mailer and inspect the finished message,
 * which is where {@see \Flarum\Mail\MutateEmail} puts the values back.
 */
class SafeSubstitutionRestoreTest extends TestCase
{
    private ?Email $sent = null;

    /**
     * Send a mail built from the given text and html views and return the
     * message as it went out, after all mutation.
     *
     * @param array<string, string> $data
     */
    private function sendMail(string $textView, string $htmlView, array $data): Email
    {
        /** @var Factory $views */
        $views = $this->app()->getContainer()->make(Factory::class);
        $views->addNamespace('flarum-core-test', __DIR__.'/../../fixtures/views');

        /** @var \Illuminate\Contracts\Events\Dispatcher $events */
        $events = $this->app()->getContainer()->make('events');
        $events->listen(MessageSent::class, function (MessageSent $event) {
            $this->sent = $event->message;
        });

        /** @var Mailer $mailer */
        $mailer = $this->app()->getContainer()->make(Mailer::class);

        $mailer->send(
            ['text' => $textView, 'html' => $htmlView],
            $data,
            function (Message $message) {
                $message->to('recipient@example.com');
                $message->subject('Test');
            }
        );

        $this->assertNotNull($this->sent, 'The mail was never sent.');

        return $this->sent;
    }

    #[Test]
    public function a_value_output_without_the_formatter_is_still_restored()
    {
        // The gdpr erasure email renders its body straight from the translator,
        // never through the formatter — this pins that its values are put back
        // regardless.
        $data = [
            'template' => 'Someone requested to erase the account `{name}`.',
            'parameters' => ['{name}' => 'Karaok'],
        ];

        $message = $this->sendMail('flarum-core-test::email.bare-trans', 'flarum-core-test::email.bare-trans', $data);

        $html = $message->getHtmlBody();
        $text = $message->getTextBody();

        $this->assertStringNotContainsString('flarumsafevalue', $html, 'A marker leaked into the html body.');
        $this->assertStringNotContainsString('flarumsafevalue', $text, 'A marker leaked into the text body.');

        $this->assertStringContainsString('Karaok', $html, 'The value must be restored in the html body.');
        $this->assertStringContainsString('Karaok', $text, 'The value must be restored in the text body.');
    }

    #[Test]
    public function a_value_output_through_the_formatter_is_restored_once()
    {
        // The value goes through the formatter here, so it was already restored
        // during rendering; the message-level restore must leave it alone
        // rather than double-process it.
        $data = [
            'template' => 'Welcome, {name}.',
            'parameters' => ['{name}' => 'Karaok'],
        ];

        $message = $this->sendMail('flarum-core-test::email.formatter', 'flarum-core-test::email.formatter', $data);

        $this->assertStringNotContainsString('flarumsafevalue', $message->getHtmlBody());
        $this->assertStringContainsString('Karaok', $message->getHtmlBody());
    }

    #[Test]
    public function a_marked_value_is_escaped_in_html_but_not_in_plain_text()
    {
        // In html the value is shown, not interpreted, so markup in it is
        // escaped. In plain text there is nothing to interpret, so it is put
        // back verbatim — no stray entities.
        $data = [
            'template' => 'Account: {name}.',
            'parameters' => ['{name}' => 'A & B <tag>'],
        ];

        $message = $this->sendMail('flarum-core-test::email.bare-trans', 'flarum-core-test::email.bare-trans', $data);

        $this->assertStringContainsString('A &amp; B &lt;tag&gt;', $message->getHtmlBody(), 'Markup in the value must be escaped in html.');
        $this->assertStringContainsString('A & B <tag>', $message->getTextBody(), 'The plain-text value must not be html-escaped.');
    }

    #[Test]
    public function a_core_mail_namespaced_view_is_protected()
    {
        // Core's own mail views live under the `mail::` namespace, whose names
        // do not contain "email". They must be marked and restored just the
        // same, or a value in a core notification would reach the reader
        // unprotected — the very thing the mail translator exists to prevent.
        //
        // A marked value comes back html-escaped; an unmarked one is emitted
        // verbatim. Feeding html metacharacters through therefore tells the two
        // apart: escaping only happens if the `mail::` view was marked.
        /** @var Factory $views */
        $views = $this->app()->getContainer()->make(Factory::class);
        // Add the fixtures dir as a second source for the real `mail` namespace,
        // so the view name starts with `mail::` the way core's own views do.
        $views->addNamespace('mail', __DIR__.'/../../fixtures/views/email');

        $data = [
            'template' => 'posted: {title}.',
            'parameters' => ['{title}' => '<b>x</b>'],
        ];

        $message = $this->sendMail('mail::bare-trans', 'mail::bare-trans', $data);

        $this->assertStringNotContainsString('flarumsafevalue', $message->getHtmlBody());
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $message->getHtmlBody(), 'The value in a core mail:: view must be marked and restored escaped.');
        $this->assertStringNotContainsString('<b>x</b>', $message->getHtmlBody(), 'An unescaped value means the mail:: view was never protected.');
    }

    #[Test]
    public function a_body_with_no_markers_is_untouched()
    {
        // A mail whose values never went through the mail translator carries no
        // markers; the restore must be a no-op, not a mangle.
        $data = ['bodyText' => 'nothing to restore here'];

        $message = $this->sendMail('flarum-core-test::email.plain-passthrough', 'flarum-core-test::email.plain-passthrough', $data);

        $this->assertStringContainsString('nothing to restore here', $message->getHtmlBody());
        $this->assertStringContainsString('nothing to restore here', $message->getTextBody());
    }
}
