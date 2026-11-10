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
 * The HTML part of an informational email (account activation, email
 * confirmation, password reset) is built from a body that was translated
 * before it reached the view, so the template receives one plain string.
 *
 * It used to be printed with `{{ }}`, which meant the address a reader is
 * asked to visit arrived as text no mail client would turn into a link, and
 * the blank lines separating the paragraphs collapsed, since a newline is not
 * a break in HTML.
 *
 * These tests render the real blade through the real view factory, the way
 * SendInformationalEmailJob does.
 */
class InformationalEmailLinksTest extends TestCase
{
    private const URL = 'https://example.com/confirm/aBcD1234';

    private function render(string $infoContent): string
    {
        /** @var Factory $view */
        $view = $this->app()->getContainer()->make(Factory::class);

        // The data SendInformationalEmailJob shares before rendering.
        $view->share([
            'forumTitle' => 'Test Forum',
            'userEmail' => 'recipient@example.com',
            'title' => null,
            'username' => 'Recipient',
        ]);

        return $view->make('mail::html.information.generic', compact('infoContent'))->render();
    }

    /**
     * The `main-content` div is the body itself. The footer and the header hold
     * links of their own, so matching the whole document would not tell us
     * anything about the body.
     */
    private function body(string $html): string
    {
        $this->assertMatchesRegularExpression('#<div class="main-content">(.*?)</div>#s', $html);
        preg_match('#<div class="main-content">(.*?)</div>#s', $html, $matches);

        return trim($matches[1]);
    }

    #[Test]
    public function url_in_an_informational_email_is_a_link(): void
    {
        $body = $this->body($this->render('Click the following link:'."\n".self::URL));

        $this->assertStringContainsString('<a href="'.self::URL.'">'.self::URL.'</a>', $body);
    }

    #[Test]
    public function line_breaks_in_an_informational_email_survive(): void
    {
        $body = $this->body($this->render("First paragraph.\n\nSecond line.\nThird line."));

        // A blank line starts a paragraph, a single newline is a break.
        $this->assertStringContainsString('<p>First paragraph.</p>', $body);
        $this->assertMatchesRegularExpression('#Second line\.<br>\s*Third line\.#', $body);
    }

    #[Test]
    public function markup_in_an_informational_email_is_not_rendered(): void
    {
        // The body carries values that were substituted by the translator
        // before the view saw them, a display name among them, so nothing in it
        // may be treated as markup.
        $body = $this->body($this->render('Hello <b>[label](https://evil.example.com)</b> & welcome.'));

        // The tags are shown, not applied.
        $this->assertStringNotContainsString('<b>', $body);
        $this->assertStringContainsString('&lt;b&gt;', $body);
        $this->assertStringContainsString('&amp;', $body);

        // The address is linked, but the markdown around it is not: the link
        // text is the address itself, never the label someone chose for it.
        $this->assertStringNotContainsString('>label</a>', $body);
        $this->assertStringContainsString('[label](<a href="https://evil.example.com">https://evil.example.com</a>)', $body);
    }

    #[Test]
    public function punctuation_after_a_url_is_left_out_of_the_link(): void
    {
        $body = $this->body($this->render('Visit '.self::URL.'.'));

        $this->assertStringContainsString('<a href="'.self::URL.'">'.self::URL.'</a>.', $body);
    }

    #[Test]
    public function a_bracket_the_url_did_not_open_is_left_out_of_the_link(): void
    {
        $body = $this->body($this->render('Visit ('.self::URL.') today'));

        $this->assertStringContainsString('(<a href="'.self::URL.'">'.self::URL.'</a>)', $body);
    }
}
