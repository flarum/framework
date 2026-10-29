<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Mail\SafeSubstitution;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\View\Factory;
use PHPUnit\Framework\Attributes\Test;

/**
 * The plain-text part of an email is text, so nothing in it may reach the
 * reader as an HTML entity, however often a view escaped it for Blade.
 */
class PlainTextEmailEscapingTest extends TestCase
{
    private const string NAME = 'Ann\'s "Q&A" <Forum>';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('forum_title', self::NAME);
    }

    /**
     * Rendered as SendInformationalEmailJob renders it, then with the values
     * MailTranslator held back put in as MutateEmail does for the text part.
     */
    private function renderInformational(string $body, ?string $title = null): string
    {
        $view = $this->app()->getContainer()->make(Factory::class);

        $view->share([
            'forumTitle' => self::NAME,
            'userEmail' => 'admin@example.com',
            'title' => $title,
            'username' => self::NAME,
        ]);

        $text = $view->make('mail::plain.information.generic', ['infoContent' => $body])->render();

        return SafeSubstitution::restore($text, escape: false);
    }

    #[Test]
    public function body_reaches_the_reader_unescaped(): void
    {
        $text = $this->renderInformational('Welcome to '.self::NAME.".\n\nhttps://example.com/?a=1&b=2");

        $this->assertStringContainsString('Welcome to '.self::NAME.'.', $text);
        $this->assertStringContainsString('https://example.com/?a=1&b=2', $text);
    }

    #[Test]
    public function header_greeting_and_signoff_reach_the_reader_unescaped(): void
    {
        $text = $this->renderInformational('Body.', 'A "quoted" title & more');

        $this->assertStringContainsString('A "quoted" title & more', $text);
        $this->assertStringContainsString('Hey '.self::NAME.',', $text);
        $this->assertStringContainsString('- The '.self::NAME.' team -', $text);
    }

    #[Test]
    public function no_entity_is_left_anywhere(): void
    {
        $text = $this->renderInformational('Welcome to '.self::NAME.'.', self::NAME);

        $this->assertDoesNotMatchRegularExpression('/&(?:amp|quot|lt|gt|#0?39);/', $text);
    }

    #[Test]
    public function markup_in_a_slot_is_still_removed(): void
    {
        $view = $this->app()->getContainer()->make(Factory::class);
        $view->share(['forumTitle' => 'Test Forum', 'userEmail' => 'admin@example.com', 'username' => 'Admin']);

        $text = $view->make('mail::plain.information', ['body' => '<p><strong>Bold</strong> text</p>'])->render();

        $this->assertStringContainsString('Bold text', $text);
        $this->assertStringNotContainsString('<strong>', $text);
    }
}
