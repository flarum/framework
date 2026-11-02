<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\unit;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Api\HtmlToText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApiResultTest extends TestCase
{
    #[Test]
    public function simplify_flattens_resources_and_dedupes_included(): void
    {
        $result = new ApiResult([
            'data' => [
                ['type' => 'posts', 'id' => '1', 'attributes' => ['contentHtml' => '<p>Hi <a href="https://x.test">there</a></p>', 'number' => 1], 'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '3']], 'likes' => ['data' => []], 'links' => ['links' => []]]],
                ['type' => 'posts', 'id' => '2', 'attributes' => ['content' => 'raw', 'contentHtml' => '<p>raw</p>'], 'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '3']]]],
            ],
            'included' => [
                ['type' => 'users', 'id' => '3', 'attributes' => ['username' => 'a']],
                ['type' => 'users', 'id' => '3', 'attributes' => ['username' => 'a']],
            ],
            'meta' => ['page' => ['total' => 2]],
        ]);

        $simple = $result->simplify();

        $this->assertSame(['type' => 'posts', 'id' => '1', 'content' => 'Hi there (https://x.test)', 'number' => 1, 'relationships' => ['user' => ['type' => 'users', 'id' => '3'], 'likes' => []]], $simple['data'][0]);
        // A raw attribute of the plain name wins over the converted HTML.
        $this->assertSame('raw', $simple['data'][1]['content']);
        $this->assertSame('<p>raw</p>', $simple['data'][1]['contentHtml']);
        $this->assertCount(1, $simple['included']);
        $this->assertSame(['page' => ['total' => 2]], $simple['meta']);
    }

    #[Test]
    public function long_strings_are_truncated(): void
    {
        $this->assertSame('abc… [truncated, 2 more characters]', ApiResult::truncate('abcde', 3));
        $this->assertSame('abc', ApiResult::truncate('abc', 3));
    }

    #[Test]
    public function oversized_lists_are_cut_with_a_next_hint(): void
    {
        $items = array_map(fn (int $i) => ['type' => 'x', 'id' => (string) $i, 'text' => str_repeat('a', 100)], range(1, 50));

        $fitted = ApiResult::fit(['data' => $items], offset: 20, limit: 1000);

        $this->assertLessThanOrEqual(1000, mb_strlen((string) json_encode($fitted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
        $this->assertTrue($fitted['meta']['truncated']);
        $this->assertSame(20 + count($fitted['data']), $fitted['meta']['next']['offset']);
        $this->assertNotEmpty($fitted['data']);
    }

    #[Test]
    public function html_becomes_readable_text(): void
    {
        $html = '<p>One&amp;two<br>three</p><ul><li>a</li><li>b</li></ul><pre><code>  x = 1
  y = 2</code></pre><p><img src="e.png" alt="😀"> <a href="https://f.test/u">https://f.test/u</a></p>';

        $this->assertSame("One&two\nthree\n\n- a\n- b\n\n  x = 1\n  y = 2\n\n😀 https://f.test/u", HtmlToText::convert($html));
    }
}
