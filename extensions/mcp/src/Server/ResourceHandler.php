<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Resource;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Server\ClientGateway;
use Mcp\Server\Handler\ResourceHandlerInterface;

/**
 * @internal
 */
class ResourceHandler implements ResourceHandlerInterface
{
    public function __construct(
        protected Resource $resource,
        protected McpContext $context,
        protected Invoker $invoker,
    ) {
    }

    public function read(string $uri, ClientGateway $gateway): mixed
    {
        $result = $this->invoker->run(
            fn () => $this->resource->read($this->context),
            // The SDK answers this with -32602 (-32002 before 2026-07-28); it has no other invalid-params path for reads.
            fn (string $message) => new ResourceNotFoundException($uri),
        );

        return ResourceContents::make($uri, $this->resource->mimeType(), $result);
    }
}
