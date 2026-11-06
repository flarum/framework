<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol;

use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Tests\integration\extend\Fixtures\StructuredResultTool;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class StructuredResultsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend((new Mcp())->tool(StructuredResultTool::class));
        $this->prepareDatabase([User::class => [$this->normalUser()]]);
    }

    #[Test]
    public function modern_results_follow_composed_output_schemas(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        foreach ([['answer' => 42], ['one', 'two'], [], 'hello', '', 42, 0, 1.5, false, true] as $value) {
            $result = $this->rpcResult($this->callTool('structured_result', ['value' => $value], $auth));

            $this->assertFalse($result['isError']);
            $this->assertArrayHasKey('structuredContent', $result);
            $this->assertSame($value, $result['structuredContent']);
            $text = $result['content'][0]['text'];
            $this->assertSame($value, is_string($value) ? $text : json_decode($text, true));
        }
    }
}
