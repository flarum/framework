<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Resource;

use Flarum\Mcp\Capability\McpContext;

class DiscussionPostsTemplate extends DiscussionTemplate
{
    public function uriTemplate(): string
    {
        return 'flarum://discussions/{id}/posts/{offset}';
    }

    public function name(): string
    {
        return 'discussion-posts';
    }

    public function title(): ?string
    {
        return 'Discussion posts';
    }

    public function description(): ?string
    {
        return 'A discussion with 20 posts from an offset, as read_discussion returns it. The paging hints (previous.uri, next.uri) are the resources with the posts before and after.';
    }

    public function read(array $variables, McpContext $context): mixed
    {
        return $this->page($context, $variables, offset: $this->offset($variables, 'offset'));
    }
}
