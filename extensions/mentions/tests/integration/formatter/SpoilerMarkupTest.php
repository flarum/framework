<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\formatter;

use Flarum\Formatter\Formatter;
use Flarum\Testing\integration\RefreshesFormatterCache;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Quoting a post finds its spoilers by the markdown extension's markup and
 * writes them back as spoilers (js/src/forum/utils/selectedText.js). If that
 * markup changes, a quoted spoiler comes out as plain text, shown to everyone.
 */
class SpoilerMarkupTest extends TestCase
{
    use RefreshesFormatterCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-markdown', 'flarum-mentions');
    }

    protected function render(string $text): string
    {
        $formatter = $this->app()->getContainer()->make(Formatter::class);

        return $formatter->render($formatter->parse($text));
    }

    #[Test]
    public function an_inline_spoiler_is_marked_so_that_it_can_be_found_once_revealed()
    {
        // Revealing an inline spoiler removes its class; data-spoiler stays.
        $html = $this->render('before >!secret!< after');

        $this->assertMatchesRegularExpression('/<span class="spoiler" data-spoiler=""[^>]*>secret<\/span>/', $html);
        $this->assertStringContainsString('onclick="removeAttribute(\'class\')"', $html);
    }

    #[Test]
    public function a_block_spoiler_is_a_details_element_with_the_spoiler_class()
    {
        $html = $this->render(">! one\n>!\n>! two");

        $this->assertMatchesRegularExpression('/<details class="spoiler">\s*<p>one<\/p>\s*<p>two<\/p>\s*<\/details>/', $html);
    }
}
