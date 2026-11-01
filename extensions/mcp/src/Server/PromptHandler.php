<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Prompt;
use Mcp\Exception\PromptGetException;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Mcp\Server\ClientGateway;
use Mcp\Server\Handler\PromptHandlerInterface;

/**
 * @internal
 */
class PromptHandler implements PromptHandlerInterface
{
    public function __construct(
        protected Prompt $prompt,
        protected McpContext $context,
        protected Invoker $invoker,
    ) {
    }

    /**
     * @return list<PromptMessage>
     */
    public function get(array $arguments, ClientGateway $gateway): mixed
    {
        $result = $this->invoker->run(
            fn () => $this->prompt->get($arguments, $this->context),
            fn (string $message) => new PromptGetException($message),
        );

        if (is_string($result)) {
            return [new PromptMessage(Role::User, new TextContent($result))];
        }

        return array_map(
            fn (array $message) => new PromptMessage(
                $message['role'] === 'assistant' ? Role::Assistant : Role::User,
                new TextContent($message['text']),
            ),
            $result,
        );
    }
}
