<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol;

use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Tests\integration\extend\Fixtures\ObjectResultTool;
use Flarum\Mcp\Tests\integration\extend\Fixtures\StructuredResultTool;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class StructuredResultsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extend((new Mcp())->tool(StructuredResultTool::class)->tool(ObjectResultTool::class));
        $this->prepareDatabase([User::class => [$this->normalUser()]]);
    }

    #[Test]
    public function empty_input_subschemas_are_published_as_json_objects(): void
    {
        $response = $this->modern('tools/list', [], $this->bearer($this->developerToken(2)));
        $this->rpcResult($response);
        $response->getBody()->rewind();
        $tools = json_decode((string) $response->getBody())->result->tools;

        foreach ($tools as $tool) {
            if ($tool->name === 'structured_result') {
                $this->assertInstanceOf(\stdClass::class, $tool->inputSchema->properties->value);

                return;
            }
        }

        $this->fail('The structured-result tool was not advertised.');
    }

    #[Test]
    public function modern_object_schemas_distinguish_empty_objects_from_invalid_arrays(): void
    {
        $auth = $this->bearer($this->developerToken(2));
        $response = $this->callTool('object_result', ['value' => 'empty-object'], $auth);
        $this->assertFalse($this->rpcResult($response)['isError']);
        $response->getBody()->rewind();
        $this->assertInstanceOf(\stdClass::class, json_decode((string) $response->getBody())->result->structuredContent);

        foreach ([[], 'not an object', 42] as $value) {
            $result = $this->rpcResult($this->callTool('object_result', ['value' => $value], $auth));
            $this->assertTrue($result['isError']);
        }
    }

    #[Test]
    public function legacy_results_only_send_objects_as_structured_content(): void
    {
        $auth = $this->bearer($this->developerToken(2));
        $session = $this->initializeSession($auth);

        foreach ([['answer' => 42], ['one', 'two'], [], 'hello', 42, false] as $value) {
            $result = $this->rpcResult($this->inSession($session, 'tools/call', [
                'name' => 'structured_result', 'arguments' => ['value' => $value],
            ], $auth));

            $this->assertFalse($result['isError']);
            $this->assertNotEmpty($result['content']);

            if ($value === ['answer' => 42]) {
                $this->assertSame(['answer' => 42], $result['structuredContent']);
            } else {
                $this->assertArrayNotHasKey('structuredContent', $result);
            }
        }
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
