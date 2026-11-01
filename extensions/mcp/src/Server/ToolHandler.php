<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\ClientGateway;
use Mcp\Server\Handler\ToolHandlerInterface;

/**
 * @internal
 */
class ToolHandler implements ToolHandlerInterface
{
    public function __construct(
        protected Tool $tool,
        protected McpContext $context,
        protected Invoker $invoker,
    ) {
    }

    public function execute(array $arguments, ClientGateway $gateway): mixed
    {
        $result = $this->invoker->run(
            fn () => $this->tool->handle($arguments, $this->context),
            fn (string $message) => new ToolCallException($message),
        );

        if (is_string($result)) {
            return new CallToolResult([new TextContent($result)]);
        }

        $structured = null;
        if (($this->tool->outputSchema()['type'] ?? null) === 'object' && is_array($result) && ! array_is_list($result)) {
            $structured = $result;
        }

        return new CallToolResult([new TextContent(Json::encode($result))], structuredContent: $structured);
    }
}
