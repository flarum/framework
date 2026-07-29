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
 * Regression test: the abandoned-extensions notification email rendered with an
 * empty body (only greeting + sign-off). The notify templates passed their body
 * via `<x-slot:content>`, but the shared `x-mail::*.information` component renders
 * `{!! $body ?? $slot ?? '' !!}` — it reads a `body` slot (or the default slot),
 * never `content`, so the body was silently dropped. Affected both HTML and plain.
 */
class AbandonedExtensionsEmailBodyTest extends TestCase
{
    private function viewFactory(): Factory
    {
        return $this->app()->getContainer()->make(Factory::class);
    }

    /**
     * Render the two views exactly as SendAbandonedExtensionsEmailJob does:
     * share forumTitle/userEmail/username, pass extensionLines as view data.
     *
     * @return array{html: string, text: string}
     */
    private function render(array $extensionLines): array
    {
        $view = $this->viewFactory();

        $view->share([
            'forumTitle' => 'Test Forum',
            'userEmail' => 'admin@example.com',
            'username' => 'Admin',
        ]);

        return [
            'html' => $view->make('mail::html.abandoned_extensions.notify', compact('extensionLines'))->render(),
            'text' => $view->make('mail::plain.abandoned_extensions.notify', compact('extensionLines'))->render(),
        ];
    }

    #[Test]
    public function html_email_contains_the_extension_lines(): void
    {
        $lines = ['Example Extension (fof/example)', 'Another Extension (acme/another)'];

        $rendered = $this->render($lines)['html'];

        foreach ($lines as $line) {
            $this->assertStringContainsString($line, $rendered, 'The HTML email body should list the abandoned extensions.');
        }
    }

    #[Test]
    public function plain_email_contains_the_extension_lines(): void
    {
        $lines = ['Example Extension (fof/example)', 'Another Extension (acme/another)'];

        $rendered = $this->render($lines)['text'];

        foreach ($lines as $line) {
            $this->assertStringContainsString($line, $rendered, 'The plain-text email body should list the abandoned extensions.');
        }
    }
}
