<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\ResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Server\ClientGateway;
use Mcp\Server\Handler\ResourceTemplateHandlerInterface;

/**
 * @internal
 */
class ResourceTemplateHandler implements ResourceTemplateHandlerInterface
{
    public function __construct(
        protected ResourceTemplate $template,
        protected McpContext $context,
        protected Invoker $invoker,
    ) {
    }

    public function read(string $uri, array $variables, ClientGateway $gateway): mixed
    {
        $result = $this->invoker->run(
            fn () => $this->template->read($variables, $this->context),
            fn (string $message) => new ResourceNotFoundException($uri),
        );

        return ResourceContents::make($uri, $this->template->mimeType(), $result);
    }
}
