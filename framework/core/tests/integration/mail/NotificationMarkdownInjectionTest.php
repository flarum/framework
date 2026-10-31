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
 * Notification email bodies are markdown templates with the discussion title
 * and the poster's display name as link text, rendered through the full
 * formatter. Interpolating those values before rendering lets them close the
 * link early and choose their own destination, or embed an image that fires on
 * open — so the values must never reach the markdown parser.
 *
 * Templates keep calling `$translator->trans()` then `$formatter->convert()`:
 * the substitution has to be safe by default, because extension templates in
 * the wild will not be updated.
 *
 * These tests use the core formatter only, which is enough to pin that the
 * values never reach the parser — core's own autolinker will happily turn a
 * url in a title into a link. The full markdown exploit (a title closing the
 * link and choosing its destination, or embedding an image) needs the markdown
 * extension, and is pinned in that extension's own suite.
 */
class NotificationMarkdownInjectionTest extends TestCase
{
    /**
     * Render a template the way an email view does, with the translator and
     * formatter those views are given.
     *
     * @param array<string, string> $parameters
     */
    private function render(string $template, array $parameters): string
    {
        $this->app();

        /** @var Factory $views */
        $views = $this->app()->getContainer()->make(Factory::class);
        $views->addNamespace('flarum-core-test', __DIR__.'/../../fixtures/views');

        return $views->make('flarum-core-test::email-body', compact('template', 'parameters'))->render();
    }

    /**
     * Render a notification body the way every email template does.
     */
    private function renderBody(string $title, string $url = 'https://forum.local/d/1'): string
    {
        return $this->render(
            'someone posted in a discussion you follow: [{title}]({url}).',
            ['{title}' => $title, '{url}' => $url]
        );
    }

    #[Test]
    public function a_title_cannot_redirect_the_notification_link()
    {
        // Closes the intended link and opens its own.
        $html = $this->renderBody('Innocent](https://evil.example.com)[rest');

        $this->assertStringNotContainsString('evil.example.com"', $html, 'The title must not become a link destination.');
        $this->assertStringNotContainsString('href="https://evil.example.com', $html);
        $this->assertStringContainsString('https://forum.local/d/1', $html, 'The real discussion link must survive.');
    }

    #[Test]
    public function a_title_cannot_embed_a_tracking_image()
    {
        // An image reference fires on open and discloses the recipient.
        $html = $this->renderBody('Hi ![](https://evil.example.com/beacon.png) there');

        $this->assertStringNotContainsString('<img', $html, 'The title must not be able to embed an image.');
        $this->assertStringNotContainsString('evil.example.com/beacon.png"', $html);
    }

    #[Test]
    public function a_bare_url_in_a_title_is_not_autolinked()
    {
        // The formatter autolinks bare URLs, so metacharacter escaping alone
        // would not be enough.
        $html = $this->renderBody('Look at https://evil.example.com now');

        $this->assertStringNotContainsString('href="https://evil.example.com', $html);
    }

    #[Test]
    public function a_title_cannot_inject_html()
    {
        $html = $this->renderBody('Bold <b>title</b> & "quoted"');

        $this->assertStringNotContainsString('<b>title</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html, 'Markup in a title must be shown as text.');
    }

    #[Test]
    public function an_ordinary_title_renders_unchanged_inside_the_link()
    {
        // The fix must not litter legitimate titles with escape characters —
        // escaping markdown metacharacters would turn this into
        // `Version 2\.0 \- what\'s new\!`.
        $html = $this->renderBody("Version 2.0 - what's new!");

        // Html-escaped, as any value placed into markup is, but otherwise
        // untouched — no backslashes.
        $this->assertStringContainsString('Version 2.0 - what&#039;s new!', $html);
        $this->assertStringNotContainsString('\\', $html, 'Titles must not gain escape characters.');
        // Core alone doesn't parse link syntax; the url is still carried
        // through for whichever formatter does.
        $this->assertStringContainsString('https://forum.local/d/1', $html);
    }

    #[Test]
    public function the_trusted_template_is_still_rendered()
    {
        // Only the substituted values are inert; the template itself is
        // trusted content and keeps whatever the configured formatter does
        // with it. Core alone doesn't parse link syntax — the markdown
        // extension adds that — so this asserts the placeholder was replaced
        // and the url survived, not the markup. Rendering of the template's
        // own markdown is covered in the markdown extension's suite.
        $html = $this->render('see [a link]({url})', ['{url}' => 'https://forum.local/x']);

        $this->assertStringContainsString('https://forum.local/x', $html);
        $this->assertStringNotContainsString('{url}', $html);
    }

    #[Test]
    public function repeated_renders_in_one_request_do_not_leak_between_each_other()
    {
        // Core's notification template calls convert() three times, and
        // extension templates render a preview alongside the body. Values from
        // one render must not appear in another.
        $first = $this->renderBody('First title');
        $second = $this->renderBody('Second title');

        $this->assertStringContainsString('First title', $first);
        $this->assertStringNotContainsString('Second title', $first);
        $this->assertStringContainsString('Second title', $second);
        $this->assertStringNotContainsString('First title', $second);
    }

    #[Test]
    public function a_display_name_is_treated_the_same_as_a_title()
    {
        // The report named titles; display names flow through the same
        // templates (and are often self-service via nicknames).
        $html = $this->render('{poster_display_name} posted: [{title}]({url}).', [
            '{poster_display_name}' => '[Admin](https://evil.example.com)',
            '{title}' => 'A title',
            '{url}' => 'https://forum.local/d/1',
        ]);

        $this->assertStringNotContainsString('href="https://evil.example.com', $html);
    }
}
