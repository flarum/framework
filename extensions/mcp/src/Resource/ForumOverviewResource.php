<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Resource;

use Flarum\Mcp\Capability\CapabilityResolver;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Resource;
use Flarum\Mcp\Overview\OverviewBuilder;

class ForumOverviewResource extends Resource
{
    public function __construct(
        protected OverviewBuilder $overview,
        protected CapabilityResolver $resolver,
    ) {
    }

    public function uri(): string
    {
        return 'flarum://forum';
    }

    public function name(): string
    {
        return 'forum';
    }

    public function title(): ?string
    {
        return 'Forum overview';
    }

    public function description(): ?string
    {
        return 'The forum, who you are and what you can do, and every enabled extension with the tools and resource types it adds.';
    }

    public function read(McpContext $context): mixed
    {
        return $this->overview->build($context, $this->resolver->resolve($context->actor));
    }
}
