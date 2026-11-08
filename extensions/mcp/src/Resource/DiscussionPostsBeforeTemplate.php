<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Resource;

use Flarum\Mcp\Capability\McpContext;

class DiscussionPostsBeforeTemplate extends DiscussionTemplate
{
    public function uriTemplate(): string
    {
        return 'flarum://discussions/{id}/posts/before/{before}';
    }

    public function name(): string
    {
        return 'discussion-posts-before';
    }

    public function title(): ?string
    {
        return 'Discussion posts before an offset';
    }

    public function description(): ?string
    {
        return 'A discussion with up to 20 posts just before an offset, as read_discussion returns it. The paging hints (previous.uri, next.uri) are the resources with the posts before and after.';
    }

    public function read(array $variables, McpContext $context): mixed
    {
        return $this->page($context, $variables, before: $this->offset($variables, 'before'));
    }
}
