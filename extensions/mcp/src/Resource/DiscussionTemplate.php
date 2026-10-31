<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Resource;

use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\ResourceTemplate;
use Flarum\Mcp\Tool\Support\DiscussionReader;

class DiscussionTemplate extends ResourceTemplate
{
    public function __construct(
        protected DiscussionReader $reader,
    ) {
    }

    public function uriTemplate(): string
    {
        return 'flarum://discussions/{id}';
    }

    public function name(): string
    {
        return 'discussion';
    }

    public function title(): ?string
    {
        return 'Discussion';
    }

    public function description(): ?string
    {
        return 'A discussion with its first 20 posts, as read_discussion returns it.';
    }

    public function read(array $variables, McpContext $context): mixed
    {
        return $this->reader->read($context, (string) ($variables['id'] ?? ''));
    }
}
