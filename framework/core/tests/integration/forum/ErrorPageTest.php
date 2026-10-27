<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Foundation\ErrorHandling\HandledError;
use Flarum\Foundation\ErrorHandling\ViewFormatter;
use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

/**
 * Error pages (and the other standalone pages sharing their layout) load none
 * of the forum's assets, so they follow its color scheme and show its favicon
 * on their own.
 *
 * Tests run in debug mode, where the forum shows Whoops instead, so the page is
 * rendered through the formatter that serves it in production.
 */
class ErrorPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('theme_secondary_color', '#536F90');
    }

    private function render(): string
    {
        /** @var ViewFormatter $formatter */
        $formatter = $this->app()->getContainer()->make(ViewFormatter::class);

        $error = new HandledError((new ModelNotFoundException())->setModel('Test'), 'not_found', 404);

        return (string) $formatter->format($error, new ServerRequest())->getBody();
    }

    #[Test]
    public function error_page_follows_the_system_color_scheme_when_the_forum_does(): void
    {
        $this->setting('color_scheme', 'auto');

        $html = $this->render();

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $html);
        $this->assertStringContainsString('--body-bg: hsl(212,20%,10%);', $html);
    }

    #[Test]
    public function error_page_is_dark_when_the_forum_is(): void
    {
        $this->setting('color_scheme', 'dark');

        $html = $this->render();

        $this->assertStringContainsString('@media all', $html);
        $this->assertStringContainsString('--body-bg: hsl(212,20%,10%);', $html);
        $this->assertStringNotContainsString('prefers-color-scheme', $html);
    }

    #[Test]
    public function error_page_is_dark_when_the_forum_is_dark_high_contrast(): void
    {
        $this->setting('color_scheme', 'dark-hc');

        $html = $this->render();

        $this->assertStringContainsString('@media all', $html);
        $this->assertStringNotContainsString('prefers-color-scheme', $html);
    }

    #[Test]
    public function error_page_is_light_when_the_forum_is(): void
    {
        $this->setting('color_scheme', 'light');

        $html = $this->render();

        $this->assertStringNotContainsString('@media', $html);
        $this->assertStringNotContainsString('color-scheme: dark', $html);
    }

    #[Test]
    public function error_page_shows_the_forum_favicon(): void
    {
        $this->setting('favicon_path', 'favicon-abc123.png');

        $this->assertStringContainsString(
            '<link rel="shortcut icon" href="http://localhost/assets/favicon-abc123.png">',
            $this->render()
        );
    }

    #[Test]
    public function error_page_has_no_favicon_when_the_forum_has_none(): void
    {
        $this->assertStringNotContainsString('rel="shortcut icon"', $this->render());
    }
}
