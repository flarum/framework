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
    public function a_list_that_cannot_be_paged_is_cut_without_a_next_hint(): void
    {
        $items = array_map(fn (int $i) => ['type' => 'x', 'id' => (string) $i, 'attributes' => ['text' => str_repeat('a', 200)]], range(1, 1000));

        $simple = (new ApiResult(['data' => $items]))->simplify(paginated: false);

        $this->assertTrue($simple['meta']['truncated']);
        $this->assertArrayNotHasKey('next', $simple['meta']);
    }

    #[Test]
    public function full_strings_keep_the_source_whole_but_not_its_html(): void
    {
        $source = str_repeat('a', 5000);

        $simple = (new ApiResult(['data' => ['type' => 'posts', 'id' => '1', 'attributes' => [
            'content' => $source,
            'contentHtml' => '<p>'.$source.'</p>',
        ]]]))->simplify(fullStrings: true);

        $this->assertSame($source, $simple['data']['content']);
        $this->assertStringContainsString('[truncated,', $simple['data']['contentHtml']);
    }

    #[Test]
    public function an_oversized_single_resource_is_cut_to_the_limit(): void
    {
        $fitted = ApiResult::fit([
            'data' => ['type' => 'x', 'id' => '1', 'short' => 'kept', 'long' => str_repeat('a', 5000)],
            'included' => [['type' => 'users', 'id' => '1', 'username' => 'u']],
        ], limit: 1000);

        $this->assertLessThanOrEqual(1000, mb_strlen((string) json_encode($fitted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
        $this->assertTrue($fitted['meta']['truncated']);
        $this->assertSame('kept', $fitted['data']['short']);
        $this->assertStringContainsString('[truncated,', $fitted['data']['long']);
    }

    #[Test]
    public function a_single_resource_within_the_limit_is_not_marked_truncated(): void
    {
        $payload = ['data' => ['type' => 'x', 'id' => '1', 'long' => str_repeat('a', 5000)]];

        $this->assertSame($payload, ApiResult::fit($payload));
    }

    #[Test]
    public function a_resource_too_big_for_its_strings_to_fix_keeps_them(): void
    {
        $ids = array_map(fn (int $i) => ['type' => 'posts', 'id' => (string) $i], range(1, 100));

        $fitted = ApiResult::fit([
            'data' => ['type' => 'discussions', 'id' => '1', 'title' => 'A title', 'relationships' => ['posts' => $ids]],
        ], limit: 1000);

        $this->assertSame('A title', $fitted['data']['title']);
        $this->assertSame('discussions', $fitted['data']['type']);
        $this->assertTrue($fitted['meta']['truncated']);
    }

    #[Test]
    public function text_that_looks_cut_already_is_still_cut(): void
    {
        $fitted = ApiResult::fit([
            'data' => ['type' => 'x', 'id' => '1', 'bio' => '[truncated, 1 more characters] '.str_repeat('a', 5000)],
        ], limit: 1000);

        $this->assertLessThanOrEqual(1000, mb_strlen((string) json_encode($fitted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    }

    #[Test]
    public function heavily_escaped_text_keeps_as_much_as_fits(): void
    {
        $fitted = ApiResult::fit(['data' => ['type' => 'x', 'id' => '1', 'text' => str_repeat('\\', 1000)]], limit: 1000);

        $length = mb_strlen((string) json_encode($fitted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->assertLessThanOrEqual(1000, $length);
        $this->assertGreaterThan(700, $length);
    }

    #[Test]
    public function html_becomes_readable_text(): void
    {
        $html = '<p>One&amp;two<br>three</p><ul><li>a</li><li>b</li></ul><pre><code>  x = 1
  y = 2</code></pre><p><img src="e.png" alt="😀"> <a href="https://f.test/u">https://f.test/u</a></p>';

        $this->assertSame("One&two\nthree\n\n- a\n- b\n\n  x = 1\n  y = 2\n\n😀 https://f.test/u", HtmlToText::convert($html));
    }

    #[Test]
    public function text_shaped_like_a_code_block_placeholder_is_left_as_written(): void
    {
        $html = "<pre><code>first</code></pre><p>a \u{E000}0\u{E000} b &#xE000;1&#xE000; c</p><pre><code>  second\n  block</code></pre>";

        $this->assertSame("first\n\na \u{E000}0\u{E000} b \u{E000}1\u{E000} c\n\n  second\n  block", HtmlToText::convert($html));
    }
}
