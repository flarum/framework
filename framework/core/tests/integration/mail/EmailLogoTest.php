<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Mail\EmailLogo;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\View\Factory;
use PHPUnit\Framework\Attributes\Test;

class EmailLogoTest extends TestCase
{
    private function resolve(): ?array
    {
        return $this->app()->getContainer()->make(EmailLogo::class)->resolve();
    }

    private function renderEmail(): string
    {
        $data = [
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

        // Render the way NotificationMailer does: share the data, then make.
        $view = $this->app()->getContainer()->make(Factory::class);
        $view->share($data);

        return $view->make('mail::html.notification', $data)->render();
    }

    #[Test]
    public function there_is_no_email_logo_without_a_logo(): void
    {
        $this->assertNull($this->resolve());
        $this->assertStringNotContainsString('Header-logo', $this->renderEmail());
    }

    #[Test]
    public function a_webp_forum_logo_is_not_used_in_emails(): void
    {
        $this->setting('logo_path', 'logo-abcdefgh.webp');

        $this->assertNull($this->resolve());
        $this->assertStringNotContainsString('logo-abcdefgh.webp', $this->renderEmail());
    }

    #[Test]
    public function a_forum_logo_from_before_webp_is_used_as_it_is(): void
    {
        $this->setting('logo_path', 'logo-abcdefgh.png');

        $logo = $this->resolve();

        $this->assertStringEndsWith('logo-abcdefgh.png', $logo['url']);
        $this->assertNull($logo['width']);
        $this->assertNull($logo['height']);

        $html = $this->renderEmail();

        $this->assertStringContainsString('logo-abcdefgh.png', $html);
        $this->assertStringNotContainsString('width="', $html);
    }

    #[Test]
    public function the_copy_of_the_forum_logo_is_used_at_half_its_stored_size(): void
    {
        $this->setting('logo_path', 'logo-abcdefgh.webp');
        $this->setting('logo_email_copy_path', 'logo-email-abcdefgh.png');
        $this->setting('logo_email_copy_size', '480x120');

        $logo = $this->resolve();

        $this->assertStringEndsWith('logo-email-abcdefgh.png', $logo['url']);
        $this->assertSame(240, $logo['width']);
        $this->assertSame(60, $logo['height']);
        $this->assertStringContainsString('width="240" height="60"', $this->renderEmail());
    }

    #[Test]
    public function an_uploaded_email_logo_is_preferred_over_the_copy(): void
    {
        $this->setting('logo_email_copy_path', 'logo-email-abcdefgh.png');
        $this->setting('logo_email_copy_size', '480x120');
        $this->setting('email_logo_path', 'email-logo-abcdefgh.png');
        $this->setting('email_logo_size', '240x120');

        $logo = $this->resolve();

        $this->assertStringEndsWith('email-logo-abcdefgh.png', $logo['url']);
        $this->assertSame(120, $logo['width']);
        $this->assertSame(60, $logo['height']);
    }

    #[Test]
    public function a_small_logo_is_still_shown_60px_tall(): void
    {
        $this->setting('logo_email_copy_path', 'logo-email-abcdefgh.png');
        $this->setting('logo_email_copy_size', '90x30');

        $logo = $this->resolve();

        $this->assertSame(180, $logo['width']);
        $this->assertSame(60, $logo['height']);
    }

    #[Test]
    public function a_very_wide_logo_is_capped_at_the_email_width(): void
    {
        $this->setting('logo_email_copy_path', 'logo-email-abcdefgh.png');
        $this->setting('logo_email_copy_size', '1120x40');

        $logo = $this->resolve();

        $this->assertSame(560, $logo['width']);
        $this->assertSame(20, $logo['height']);
    }

    #[Test]
    public function an_unreadable_size_leaves_the_dimensions_out(): void
    {
        $this->setting('logo_email_copy_path', 'logo-email-abcdefgh.png');
        $this->setting('logo_email_copy_size', 'nonsense');

        $logo = $this->resolve();

        $this->assertStringEndsWith('logo-email-abcdefgh.png', $logo['url']);
        $this->assertNull($logo['width']);
        $this->assertNull($logo['height']);
    }
}
