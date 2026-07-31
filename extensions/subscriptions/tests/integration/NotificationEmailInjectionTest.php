<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\View\Factory;
use PHPUnit\Framework\Attributes\Test;

/**
 * The notification email body is this markdown template, with the discussion
 * title as the link text:
 *
 *     {poster_display_name} just posted in a discussion you're following: [{title}]({url}).
 *
 * With the markdown extension enabled the string is parsed as markdown, so a
 * title containing `](...)` closes the intended link and opens one of its own —
 * pointing the notification wherever its author likes — and an image reference
 * turns the email into a tracking beacon that fires when the mail is opened.
 *
 * Core's own tests pin that substituted values never reach the parser; this
 * pins the same guarantee for the configuration where the exploit is worst,
 * using the real template string and the real formatter.
 */
class NotificationEmailInjectionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * The body template, copied from `locale/en.yml`
     * (`flarum-subscriptions.email.new_post.html.body`).
     *
     * @todo Translate the key instead of copying the string, once the testing
     *       suite registers `Extend\Locales` translations — see
     *       https://github.com/flarum/framework/issues/4600, planned for 2.1.
     *       Until then `trans()` returns the key itself here, leaving nothing
     *       to attack and making the test meaningless.
     */
    private const TEMPLATE = "{poster_display_name} just posted in a discussion you're following: [{title}]({url}).";

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-subscriptions');
        // Markdown is what parses the template's link syntax, and so what
        // makes the injection reachable at all.
        $this->extension('flarum-markdown');
    }

    /**
     * Render the notification body the way the email view does.
     */
    private function renderBody(string $title, string $posterName = 'Poster'): string
    {
        $this->app();

        /** @var Factory $views */
        $views = $this->app()->getContainer()->make(Factory::class);

        // Rendered as a view named like an email one, so it gets the same
        // translator and formatter the real notification templates do.
        $views->addNamespace('flarum-subscriptions-test', __DIR__.'/../fixtures/views');

        return $views->make('flarum-subscriptions-test::email-body', [
            'template' => self::TEMPLATE,
            'parameters' => [
                '{poster_display_name}' => $posterName,
                '{title}' => $title,
                '{url}' => 'https://forum.local/d/100-a-discussion',
            ],
        ])->render();
    }

    #[Test]
    public function a_discussion_title_cannot_point_the_notification_at_another_site()
    {
        $body = $this->renderBody('Innocent](https://evil.example.com)[rest');

        $this->assertStringNotContainsString('href="https://evil.example.com', $body);
        $this->assertStringContainsString('forum.local/d/100', $body, 'The genuine discussion link must survive.');
    }

    #[Test]
    public function a_discussion_title_cannot_embed_a_tracking_image()
    {
        $body = $this->renderBody('Hi ![](https://evil.example.com/beacon.png) there');

        // The url may appear as visible text — that is the point, it is shown
        // rather than acted on. What must not happen is it being loaded.
        $this->assertStringNotContainsString('<img', $body);
        $this->assertStringNotContainsString('src="https://evil.example.com', $body);
    }

    #[Test]
    public function a_bare_url_in_a_title_is_not_autolinked()
    {
        $body = $this->renderBody('Look at https://evil.example.com now');

        $this->assertStringNotContainsString('href="https://evil.example.com', $body);
    }

    #[Test]
    public function a_display_name_cannot_point_the_notification_at_another_site()
    {
        $body = $this->renderBody('A normal title', '[Admin](https://evil.example.com)');

        $this->assertStringNotContainsString('href="https://evil.example.com', $body);
    }

    #[Test]
    public function an_ordinary_title_still_renders_as_the_link_text()
    {
        // The fix must not litter real titles: escaping markdown
        // metacharacters would render this as `Version 2\.0 \- what\'s new\!`.
        $body = $this->renderBody("Version 2.0 - what's new!");

        // The apostrophe is html-escaped, as any value placed into markup is.
        $this->assertStringContainsString('Version 2.0 - what&#039;s new!', $body);
        $this->assertStringNotContainsString('\\', $body);
    }

    #[Test]
    public function the_trusted_template_is_still_parsed_as_markdown()
    {
        // Only the values are inert; the template itself is trusted content
        // and must keep rendering as a link.
        $body = $this->renderBody('A normal title');

        $this->assertStringContainsString('<a href="https://forum.local/d/100-a-discussion"', $body);
        $this->assertStringContainsString('>A normal title</a>', $body);
    }
}
