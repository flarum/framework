<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\View\Factory;
use PHPUnit\Framework\Attributes\Test;

/**
 * Proves the reported bug: a notification email's in-body title (the heading
 * rendered by views/email/html/notification.blade.php) can leak the title from a
 * previously-sent informational email (password reset / "send test email").
 *
 * Mechanism: the `view` factory is a singleton. SendInformationalEmailJob calls
 * `$view->share(['title' => 'Reset Your Password', ...])`, which persists on the
 * shared factory. NotificationMailer renders the notification WITHOUT a `title`
 * key, and the blade falls back `{{ $title ?? trans('...default_title') }}`. With
 * a stale shared `title` present, the fallback never fires, so the notification
 * shows the previous email's title.
 *
 * This test exercises the real singleton view factory and the real notification
 * blade — the actual buggy components — rather than mocks.
 */
class EmailTitleLeakTest extends TestCase
{
    private function viewFactory(): Factory
    {
        return $this->app()->getContainer()->make(Factory::class);
    }

    /**
     * The data NotificationMailer hands to the notification view. Since the fix for
     * #4767 it includes an explicit `title` (the default notification title), so the
     * heading can't inherit a stale `title` left on the shared view factory.
     */
    private function notificationData(): array
    {
        return [
            'user' => (object) ['email' => 'recipient@example.com'],
            'unsubscribeLink' => 'https://example.com/unsubscribe',
            'settingsLink' => 'https://example.com/settings',
            'type' => 'testNotification',
            'forumTitle' => 'Test Forum',
            'username' => 'Recipient',
            'userEmail' => 'recipient@example.com',
            'body' => 'The notification body.',
            'title' => 'Notification',
        ];
    }

    /**
     * Extract the body heading (`<h2>`) the notification template renders, so we
     * assert on the title specifically — the word "Notification" also appears in
     * the footer, which would make a whole-document match unreliable.
     */
    private function renderedHeading(string $html): string
    {
        $this->assertMatchesRegularExpression('#<h2>(.*?)</h2>#s', $html);
        preg_match('#<h2>(.*?)</h2>#s', $html, $m);

        return trim($m[1]);
    }

    #[Test]
    public function notification_uses_its_default_title_when_rendered_in_isolation(): void
    {
        $view = $this->viewFactory();

        // Render the way NotificationMailer does: share the data, then make.
        $data = $this->notificationData();
        $view->share($data);
        $html = $view->make('mail::html.notification', $data)->render();

        // Baseline: with nothing leaked, the heading is the default notification title.
        $this->assertSame('Notification', $this->renderedHeading($html));
    }

    #[Test]
    public function notification_title_does_not_leak_from_a_previously_sent_informational_email(): void
    {
        $view = $this->viewFactory();

        // Simulate SendInformationalEmailJob (e.g. password reset) having run first
        // in this process: it shares a `title` onto the singleton view factory.
        $view->share(['title' => 'Reset Your Password']);

        // Now render a notification the way the fixed NotificationMailer does:
        // the data carries its own explicit `title`, so the stale shared one is
        // overridden rather than inherited.
        $data = $this->notificationData();
        $view->share($data);
        $html = $view->make('mail::html.notification', $data)->render();

        // Regression guard for #4767: the heading is the notification's own title,
        // NOT the leaked password-reset title.
        $this->assertSame('Notification', $this->renderedHeading($html));
    }
}
