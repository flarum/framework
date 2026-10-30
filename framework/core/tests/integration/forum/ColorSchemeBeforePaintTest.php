<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The page head settles the colour scheme before first paint. What it decides
 * is covered by js/tests/unit/preboot/colorScheme.test.ts; these tests cover
 * what the server hands it.
 */
class ColorSchemeBeforePaintTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                ['preferences' => json_encode(['colorScheme' => 'light'])] + $this->normalUser(),
            ],
        ]);
    }

    private function page(array $options = []): string
    {
        $response = $this->send($this->request('GET', '/', $options));

        $this->assertEquals(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    #[Test]
    public function the_head_runs_the_script_with_the_forum_scheme_before_the_stylesheet()
    {
        $html = $this->page();

        $script = strpos($html, 'function applyColorScheme(forumScheme)');
        $call = strpos($html, 'applyColorScheme("auto")');
        $stylesheet = strpos($html, 'rel="stylesheet"');

        $this->assertNotFalse($script);
        $this->assertNotFalse($call);
        $this->assertLessThan($stylesheet, $call);
    }

    #[Test]
    public function the_script_is_given_the_scheme_a_forum_sets()
    {
        $this->setting('color_scheme', 'dark-hc');

        $this->assertStringContainsString('applyColorScheme("dark-hc")', $this->page());
    }

    #[Test]
    public function the_page_starts_from_a_users_own_scheme()
    {
        $html = $this->page(['authenticatedAs' => 2]);

        $this->assertMatchesRegularExpression('/<html[^>]*class="logged-in"[^>]*data-theme="light"/', $html);
    }

    #[Test]
    public function a_guest_page_starts_from_auto()
    {
        $this->assertMatchesRegularExpression('/<html[^>]*class="guest-user"[^>]*data-theme="auto"/', $this->page());
    }
}
