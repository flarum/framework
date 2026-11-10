<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Mcp\Api\ResourceExposure;
use Flarum\Mcp\Overview\ExtensionOwnership;

/**
 * Shares the services whose answers the generic tools, describe_api and the
 * overview all ask for during one MCP request, so each is computed once.
 * There is no extender for container bindings.
 */
class McpServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Memoises what it exposes per actor object, which lives as long as the request.
        $this->container->singleton(ResourceExposure::class);

        // Its namespace map only changes when extensions are enabled or disabled.
        $this->container->singleton(ExtensionOwnership::class);
    }
}
