<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Integration\Likes;

class UnlikePostTool extends LikeTool
{
    public function name(): string
    {
        return 'unlike_post';
    }

    public function title(): string
    {
        return 'Unlike a post';
    }

    public function description(): string
    {
        return 'Remove your like from a post. Unliking a post you do not like changes nothing.';
    }

    protected function liked(): bool
    {
        return false;
    }
}
