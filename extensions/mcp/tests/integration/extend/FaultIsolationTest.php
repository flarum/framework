<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend;

use Flarum\Extend;
use Flarum\Mcp\Extend\Mcp;
use Flarum\Mcp\Tests\integration\extend\Fixtures\EchoTool;
use Flarum\Mcp\Tests\integration\extend\Fixtures\RecordingReporter;
use Flarum\Mcp\Tests\integration\extend\Fixtures\ThrowingPrompt;
use Flarum\Mcp\Tests\integration\extend\Fixtures\ThrowingResource;
use Flarum\Mcp\Tests\integration\extend\Fixtures\ThrowingTool;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * One extension's broken capability is reported and left out; /mcp keeps working.
 */
class FaultIsolationTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingReporter::$messages = [];
        ThrowingTool::$throwFrom = '';

        $this->extend(
            (new Extend\ErrorHandling())->reporter(RecordingReporter::class),
            (new Mcp())
                ->tool(EchoTool::class)
                ->tool(ThrowingTool::class)
                ->resource(ThrowingResource::class)
                ->prompt(ThrowingPrompt::class)
                ->instructions(fn () => throw new \RuntimeException('Instructions broke.'))
                ->instructions('Still here.')
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function brokenMethods(): array
    {
        return array_combine(
            $methods = ['name', 'title', 'description', 'inputSchema', 'outputSchema', 'readOnly'],
            array_map(fn ($method) => [$method], $methods),
        );
    }

    #[Test]
    #[DataProvider('brokenMethods')]
    public function a_tool_that_throws_from_its_metadata_is_left_out(string $method): void
    {
        ThrowingTool::$throwFrom = $method;
        $auth = $this->bearer($this->developerToken(2));

        $names = $this->toolNames($this->modern('tools/list', [], $auth));
        $this->assertNotContains('throwing', $names);
        $this->assertContains('echo', $names);
        $this->assertContains("ThrowingTool::$method() broke.", RecordingReporter::$messages);

        // The auth middleware looks the tool up too, and must not trip on it.
        $call = $this->json($this->callTool('throwing', [], $auth));
        $this->assertArrayHasKey('error', $call);
        $this->assertArrayNotHasKey('result', $call);

        $this->assertSame(['echo' => 'hi', 'by' => 'normal'], $this->toolOutput($this->callTool('echo', ['text' => 'hi'], $auth)));
    }

    #[Test]
    public function a_tool_definition_the_sdk_refuses_is_left_out(): void
    {
        ThrowingTool::$throwFrom = 'invalidSchema';

        $names = $this->toolNames($this->modern('tools/list', [], $this->bearer($this->developerToken(2))));

        $this->assertNotContains('throwing', $names);
        $this->assertContains('echo', $names);
        $this->assertNotEmpty(RecordingReporter::$messages);
    }

    #[Test]
    public function a_schema_extender_that_throws_is_dropped_but_the_tool_stays(): void
    {
        $this->extend(
            (new Mcp())->extendTool(
                'echo',
                schema: fn (array $schema) => throw new \RuntimeException('Schema extender broke.'),
                handler: fn (array $arguments, array $query) => throw new \LogicException('Never called.'),
            )
        );

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $this->bearer($this->developerToken(2))))['tools'], null, 'name');

        $this->assertSame(['text'], array_keys($tools['echo']['inputSchema']['properties']));
        $this->assertContains('Schema extender broke.', RecordingReporter::$messages);
    }

    #[Test]
    public function resources_prompts_and_instructions_that_throw_are_left_out(): void
    {
        $auth = $this->bearer($this->developerToken(2));

        $resources = $this->rpcResult($this->modern('resources/list', [], $auth));
        $this->assertNotContains('test://throwing', array_column($resources['resources'], 'uri'));
        $this->assertContains('flarum://forum', array_column($resources['resources'], 'uri'));

        $prompts = $this->rpcResult($this->modern('prompts/list', [], $auth));
        $this->assertNotContains('throwing', array_column($prompts['prompts'], 'name'));
        $this->assertContains('summarize_discussion', array_column($prompts['prompts'], 'name'));

        $discover = $this->rpcResult($this->modern('server/discover', [], $auth));
        $this->assertStringContainsString('Still here.', $discover['instructions']);

        foreach (['ThrowingResource::description() broke.', 'ThrowingPrompt::arguments() broke.', 'Instructions broke.'] as $message) {
            $this->assertContains($message, RecordingReporter::$messages);
        }
    }

    #[Test]
    public function the_admin_capability_list_survives_a_broken_tool(): void
    {
        ThrowingTool::$throwFrom = 'readOnly';

        $response = $this->send($this->request('GET', '/api/mcp-capabilities', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode());
        $names = array_column(array_column($this->json($response)['data'], 'attributes'), 'name');
        $this->assertContains('echo', $names);
        $this->assertNotContains('throwing', $names);
    }
}
